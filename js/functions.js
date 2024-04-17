const SAMPLE_COLORS = {
    "SLV": "#ccccff",
    "WBEDTA": "#ccffff",
    "SKA": "#ccffcc",
    "PrevSKA": "#ccffcc",
    "SKU": "#ffffcc",
    "SKSL1": "#ffffcc",
    "SKSL2": "#ffffcc",
    "BF1": "#ffcccc",
    "BFS1": "#ff9999",
    "BFC1": "#ff6666",
    "BF2": "#ffcccc",
    "BFS2": "#ff9999",
    "BFC2": "#ff6666",
    "BF3": "#ffcccc",
    "BFS3": "#ff9999",
    "BFC3": "#ff6666",
    "BF4": "#ffcccc",
    "BFS4": "#ff9999",
    "BFC4": "#ff6666",
    "WBSST": "#99ffcc",
    "WBRPAX": "#66ffcc",
    "PBMC": "#ffcccc",
    "PLSM": "#ff99cc",
    "SERM": "#ff6699"
};

function getShippingIds(ajaxurl,project_id,shipping_id) {
    let tracking_num = $('#'+shipping_id).val();
    $.ajax({
        url: ajaxurl,
        data: {
            project_id: project_id,
            track_num: tracking_num,
            process: 'get_shipping_ids'
        },
        type: 'POST'
    }).done(function (html) {
        //console.log(html);
        let trackSelect = $('#' + shipping_id);
        let trackList = JSON.parse(html);

        for (const key in trackList) {
            const value = trackList[key];
            var o = new Option(value, value);
            trackSelect.append(o);
        }
        trackSelect.select2();
    });
}

function loadShippingInfo(ajaxurl,project_id,tracking_id,parent_id) {
    let tracking_num = $('#'+tracking_id).val();

    $.ajax({
        url: ajaxurl,
        data: {
            project_id: project_id,
            track_num: tracking_num,
            process: 'shipping_info'
        },
        type: 'POST'
    }).done(function (html) {
        if (html != "") {
            //console.log(html);
            $('#' + parent_id).css('display', 'block');
            let shippingInfo = JSON.parse(html);
            let shipHTML = "<table><tr><th colspan='2'>Shipping Info</th></tr>" +
                "<tr><td>Shipped Date</td><td>" + shippingInfo['ship_date'] + "</td></tr>" +
                "<tr><td>Shipped By</td><td>" + shippingInfo['shipped_by'] + "</td></tr>" +
                "</table>";
            $('#' + parent_id).html(shipHTML);
        }
    });
}

function loadShippingSamples(ajaxurl,project_id,tracking_id,sample_element) {
    $('#sample_list').css('display','block');
    $('#container_container').css('display','table-cell');
    let tracking_num = $('#'+tracking_id).val();

    $.ajax({
        url: ajaxurl,
        data: {
            project_id: project_id,
            track_num: tracking_num,
            process: 'sample_list'
        },
        type: 'POST'
    }).done(function (html) {
        //console.log(html);
        let sampleList = JSON.parse(html);
        let sampleHTML = "<table id='sample_table' data-page-length='25'>";
        let headerHTML = "";
        let bodyHTML = "";

        if ('field_list' in sampleList) {
            let field_list = sampleList['field_list'];
            headerHTML = '<thead><tr>';
            bodyHTML = '<tbody>';

            if ('data' in sampleList && 'headers' in sampleList) {
                let sample_data = sampleList['data'];
                let headers = sampleList['headers'];
                for (const key in field_list) {
                    if (field_list[key] in headers) {
                        headerHTML += "<td>"+headers[field_list[key]]+"</td>"
                    }
                }
                for (const sampleid in sample_data) {
                    let sample = sample_data[sampleid];
                    let back_color = "";
                    if ("sample__status" in sample && sample["sample__status"] != "") {
                        let status = sample["sample__status"];
                        if (status.includes("Discrepencies:")) {
                            back_color = "pink";
                        }
                        else if (status.includes("Stored:")) {
                            back_color = "lightgreen";
                        }
                    }
                    bodyHTML += "<tr style='background-color: "+back_color+"'>";
                    for (const key in field_list) {
                        if (field_list[key] in sample) {
                            bodyHTML += "<td>"+sample[field_list[key]]+"</td>"
                        }
                    }
                    bodyHTML += "</tr>";
                }
            }
            headerHTML += '</tr></thead>';
            bodyHTML += '</tbody>';
        }
        sampleHTML += headerHTML+bodyHTML+"</table>";
        $('#' + sample_element).html(sampleHTML);
        $('#sample_table').DataTable({
            "scrollY": 150,
            "scrollX":true,
            "stripeClasses": []
        });
    });
}

function loadAllContainers(ajaxurl,project_id,table_id) {
    $.ajax({
        url: ajaxurl,
        data: {
            project_id: project_id,
            process: 'container_list'
        },
        type: 'POST'
    }).done(function (html) {
        //console.log(html);
        let containerSelect = $('#' + table_id);
        let containerList = JSON.parse(html);

        for (const key in containerList) {
            let container = containerList[key];
            const value = container['name'] + " (" + container['sampleCount'] + " samples stored)";
            var o = new Option(value, key);
            containerSelect.append(o);
        }
        containerSelect.select2();
    });
}

function loadContainer(ajaxurl,project_id,event_id,record,table_id,parent_id = '') {
    $.ajax({
        url: ajaxurl,
        data: {
            project_id: project_id,
            record: record,
            process: 'slot_info'
        },
        type: 'POST'
    }).done(function (html) {
        //console.log(html);
        let slotList = JSON.parse(html);
        let previousRow = "";
        let currentRow = "";
        let tableHTML = "<table><tr>";
        for (const key in slotList) {
            let value = slotList[key];
            let slotID = value['project_id'] + "_" + value['record'] + "_" + value['event'] + "_" + value['instance'];
            let slotLabel = value['slot'];
            let currentRow = slotLabel.substring(0, 1);
            let back_color = "";

            if (value['actual_type'] in SAMPLE_COLORS) {
                back_color = SAMPLE_COLORS[value['actual_type']];
            }
            if (previousRow != "" && currentRow != previousRow) {
                tableHTML += "</tr><tr>";
            }
            tableHTML += "<td><span class='slot_label'>" + slotLabel + "</span>";
            if (value['sample_id'] != "") {
                //tableHTML += "<div id='sample_slot_" + slotID + "'>Part. ID: " + value['participant_id'] + "<br/>Samp. ID: " + value['sample_id'] + "<br/>Sample Type: " + value['planned_type'] + "<br/>Collect Date: " + value['collect_date'] + "<br/><input value='Checkout' type='button' id='sample_checkout_" + slotLabel + "' onclick='checkoutSample(\""+ajaxurl+"\",\""+project_id+"\",\""+event_id+"\",\""+value['sample_id']+"\",\""+slotID+"\",\""+slotLabel+"\",\"barcode_slot_"+value['project_id'] + "_" + value['record'] + "_" + value['event'] + "_" + (value['instance']+1)+"\");' /></div>";
                tableHTML += "<div style='background-color: "+back_color+"' id='sample_slot_" + slotID + "'>Part. ID: " + value['participant_id'] + "<br/>Samp. ID: " + value['sample_id'] + "<br/>Sample Type: " + value['actual_type'] + "<br/>Collect Date: " + value['collect_date'] + "<br/><input value='Sample Info' type='button' id='sample_info_" + slotLabel + "' onclick='retrieveSampleInfo(\""+ajaxurl+"\",\""+project_id+"\",\""+event_id+"\",\""+value['sample_id']+"\",\""+slotID+"\",\""+slotLabel+"\",\"barcode_slot_"+value['project_id'] + "_" + value['record'] + "_" + value['event'] + "_" + (value['instance']+1)+"\");' /></div>";
            } else {
                tableHTML += "<div id='sample_slot_" + slotID + "'><span class='scan_barcode'><label for='barcode_slot_" + slotID + "'>Scan Barcode:</label><input class='barcode_text' type='text' id='barcode_slot_" + slotID + "' oninput='saveSample(\""+ajaxurl+"\",\""+project_id+"\",\""+event_id+"\",this.value,\"" + slotID + "\",\"" + slotLabel + "\",\"sample_issue_\",\"container_select\");loadSample(\""+ajaxurl+"\",\""+project_id+"\",\""+event_id+"\",this.value,\"" + slotID + "\",\"" + slotLabel + "\",\"barcode_slot_"+value['project_id'] + "_" + value['record'] + "_" + value['event'] + "_" + (value['instance']+1)+"\");' /></span></div>";
            }
            tableHTML += "</td>";
            previousRow = currentRow;
        }
        tableHTML += "</tr></table>";
        $('#' + table_id).html('').append(tableHTML);
        //TODO This should really be recoded to only reset the slot having a sample removed at time of saveSample function instead of reloading the whole table.
        if (parent_id != '') {
            $('#' + parent_id).parent().nextAll().find('.barcode_text').first().focus();
        }
    });
}

function loadSample(ajaxurl,project_id,event_id,barcode,slot_id,slot_label,input_next) {
    if (barcode != '') {
        retrieveSampleInfo(ajaxurl, project_id, event_id, barcode, slot_id, slot_label,input_next);
    }
}

function retrieveSampleInfo(ajaxurl,project_id,event_id,barcode,slot_id,slot_label,input_next) {
    let parent_id = "sample_slot_"+slot_id;
    let sampleTable = "";
    $.ajax({
        url: ajaxurl,
        data: {
            project_id: project_id,
            record: barcode,
            process: 'load_sample'
        },
        type: 'POST'
    }).done(function (html) {
        //console.log(html);
        if (html != "") {
            let resultData = JSON.parse(html);
            //console.log(resultData);
            for (const key in resultData) {
                if (key == "") continue;
                sampleTable = "<table><tr><td>Sample ID</td><td>" + barcode + "</td></tr>";
                let sampleData = resultData[key];
                let planned_back = "";
                let actual_back = "";
                if (sampleData['planned_type'] in SAMPLE_COLORS) {
                    planned_back = SAMPLE_COLORS[sampleData['planned_type']];
                }
                if (sampleData['actual_type'] in SAMPLE_COLORS) {
                    actual_back = SAMPLE_COLORS[sampleData['actual_type']];
                }
                sampleTable += "<tr><td>Participant ID</td><td>" + sampleData['participant_id'] + "</td></tr>" +
                    "<tr><td>Collection Date</td><td>" + sampleData['collect_date'] + "</td></tr>" +
                    "<tr><td style='background-color: "+planned_back+"'><h5>Expected Type</h5><br/>" + sampleData['planned_type'] + "</td><td style='background-color: "+actual_back+"'><h5>EDC Type</h5><br/>" + sampleData['actual_type'] + "</td></tr>" +
                    "<tr><td><h5>Expected Collect Event</h5><br/>" + sampleData['planned_collect'] + "</td><td><h5>EDC Collect Event</h5><br/>" + sampleData['actual_collect'] + "</td></tr><tr><td>Issues</td><td>";

                let discreps = sampleData['discreps'];
                for (const index in discreps) {
                    let discrepOption = discreps[index];
                    sampleTable += "<span><input id='sample_issue_"+index+"' type='checkbox' "+discrepOption['value']+" value='"+index+"' /><label for='sample_issue_"+index+"'>"+discrepOption['label']+"</label></span><br/>";
                }
                sampleTable += "</td></tr><tr><td colspan='2'><label for='sample_issue_other'>Other Notes</label><textarea id='sample_issue_other' name='sample_issue_other'>"+sampleData['discrep_other']+"</textarea></td></tr>";
                sampleTable += "<tr><td colspan='2' style='text-align:center;'><input type='button' onclick='saveSample(\""+ajaxurl+"\",\""+project_id+"\",\""+event_id+"\",\"" + barcode + "\",\"" + parent_id + "\",\"" + slot_label + "\",\"sample_issue_\",\"container_select\");$(\"#sample_info_container\").css(\"display\",\"none\");' value='Save Sample' /></td></tr>";
                //$('#' + parent_id).html("Part. ID: " + sampleData['participant_id'] + "<br/>Samp. ID: " + sampleData['sample_id'] + "<br/>Sample Type: " + sampleData['planned_type'] + "<br/>Collect Date: " + sampleData['collect_date']+ "<br/><input value='Checkout' type='button' id='sample_checkout_" + slot_label + "' onclick='checkoutSample(\""+ajaxurl+"\",\""+project_id+"\",\""+event_id+"\",\""+barcode+"\",\""+slot_id+"\",\""+slot_label+"\",\""+input_next+"\");' />");
                //$('#' + parent_id).html("<span style='background-color: "+actual_back+"'>Part. ID: " + sampleData['participant_id'] + "<br/>Samp. ID: " + sampleData['sample_id'] + "<br/>Sample Type: " + sampleData['planned_type'] + "<br/>Collect Date: " + sampleData['collect_date']+ "<br/><input value='Sample Info' type='button' id='sample_info_" + slot_label + "' onclick='retrieveSampleInfo(\""+ajaxurl+"\",\""+project_id+"\",\""+event_id+"\",\""+barcode+"\",\""+slot_id+"\",\""+slot_label+"\",\""+input_next+"\");' /></span>");

                sampleTable += "</table>";
            }
            if (sampleTable != "") {
                $('#sample_info_container').css('display', 'table-cell');
                $('#sample_info').html(sampleTable);
            }
        }
    });
    //return "<table><tr><td>Sample ID</td><td>"+barcode+"</td></tr><tr><td>Sample Type</td><td>Blood</td></tr><tr><td>Issues</td><td><span><input id='sample_issue_1' type='checkbox' value='1' /><label for='sample_issue_1'>Empty</label></span><br/><span><input id='sample_issue_2' type='checkbox' value='2' /><label for='sample_issue_2'>Wrong Sample Type</label></span><br/><span><input id='sample_issue_3' type='checkbox' value='3' /><label for='sample_issue_3'>Sample Missing</label></span><br/><span><input id='sample_issue_4' type='checkbox' value='4' /><label for='sample_issue_4'>Damaged Sample</label></span><br/><span><input id='sample_issue_5' type='checkbox' value='5' /><label for='sample_issue_5'>Damaged Tube</label></span></td></tr><tr><td colspan='2'><label for='sample_issue_other'>Other Notes</label><textarea id='sample_issue_other' name='sample_issue_other'></textarea></td></tr><tr><td colspan='2' style='text-align:center;'><input type='button' onclick='saveSample(\""+barcode+"\",\""+parent_id+"\",\"sample_issue_\");$(\"#sample_info_container\").css(\"display\",\"none\");' value='Save Sample' /></td></tr></table>";
}

function saveSample(ajaxurl,project_id,event_id,barcode,slot_id,slot_label,issue_id_prefix,container_id) {
    let discrepData = [];
    let sample_cell_id = "sample_slot_" + slot_id;
    let container = $('#' + container_id + ' option:selected');
    $("input[id^='" + issue_id_prefix + "']").each(function () {
        if ($(this).prop("checked")) {
            discrepData.push($(this).val());
        }
    });
    let discrep_other = $("#" + issue_id_prefix + "other").val();
    if (barcode != "") {
        $.ajax({
            url: ajaxurl,
            data: {
                project_id: project_id,
                record: barcode,
                discreps: discrepData,
                discrep_other: discrep_other,
                slot_setting: slot_id,
                slot_label: slot_label,
                event_id: event_id,
                process: 'save_sample'
            },
            type: 'POST'
        }).done(function (html) {
            //console.log(html);
            let result = JSON.parse(html);
            //console.log(result);
            let parent_id = "sample_slot_"+slot_id;
            loadContainer(ajaxurl, project_id, event_id, container.val(), 'container_table',parent_id);
            if (result['stored']) {
                $('#sample_row_' + barcode).css('background-color', 'lightgreen').find('td:eq(1)').html('Stored');
                $('#sample_row_' + barcode).find('td:eq(2)').html(container.text() + '<br/>' + slot_label);
            } else {
                $('#sample_row_' + barcode).css('background-color', 'lightgreen').find('td:eq(1)').html('');
                $('#sample_row_' + barcode).find('td:eq(2)').html('');
            }
            if (result['discreps'] != "") {
                $('#sample_row_' + barcode).css('background-color', 'pink').find('td:eq(1)').html(result['discreps']);
                $('#sample_row_' + barcode).find('td:eq(2)').html(container.text() + '<br/>' + slot_label);
            }
        });
    }
}

function checkoutSample(ajaxurl,project_id,event_id,barcode,slot_id,slot_label,input_focus_id) {
    let parent_id = "sample_slot_"+slot_id;

    $.ajax({
        url: ajaxurl,
        data: {
            project_id: project_id,
            record: barcode,
            slot_setting: slot_id,
            process: 'checkout_sample'
        },
        type: 'POST'
    }).done(function (html) {
        //console.log(html);
        let result = JSON.parse(html);
        if (result['removed']) {
            $('#' + parent_id).html("<span class='scan_barcode'><label for='barcode_slot_" + slot_id + "'>Scan Barcode:</label><input class='barcode_text' type='text' id='barcode_slot_" + slot_id + "' oninput='saveSample(\""+ajaxurl+"\",\""+project_id+"\",\""+event_id+"\",this.value,\"" + slot_id + "\",\"" + slot_label + "\",\"sample_issue_\",\"container_select\");loadSample(\""+ajaxurl+"\",\""+project_id+"\",\""+event_id+"\",this.value,\"" + slot_id + "\",\"" + slot_label + "\",\""+input_focus_id+"\");' /></span>");
            $('#sample_row_' + barcode).css('background-color', 'lightgreen').find('td:eq(1)').html('');
            $('#sample_row_' + barcode).find('td:eq(2)').html('');
        }
    });
}