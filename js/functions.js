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
        let sampleHTML = "<table id='sample_table' data-page-length='25'><thead><tr><th>Sample Barcode</th><th>Sample Status</th><th>Location</th></tr></thead><tbody>";
        let sampleData = "";
        for (const key in sampleList) {
            const value = sampleList[key]['sample_id'];
            const container = sampleList[key]['container'] + ' ' + sampleList[key]['slot'];
            const discreps = sampleList[key]['discrep'];
            const discrep_other = sampleList[key]['discrep_other'];
            let back_color = "";
            let status = "";
            if (discrep_other != "" || discreps != "") {
                back_color = "pink";
                status = discreps + "<br/>" + discrep_other;
            } else if (container != " ") {
                back_color = "lightgreen";
                status = 'Stored';
            }
            sampleHTML += "<tr style='background-color:" + back_color + "' id='sample_row_" + value + "'><td>" + value + "</td><td>" + status + "</td><td>" + container + "</td></tr>";
        }
        sampleHTML += "</tbody></table>";
        $('#' + sample_element).html(sampleHTML);
        $('#sample_table').DataTable({
            "scrollY": 150,
            "columns": [
                {"width": "20%"},
                {"width": "50%"},
                {"width": "30%"}
            ],
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

function loadContainer(ajaxurl,project_id,event_id,record,table_id,container = '') {
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
            const value = slotList[key];
            const slotID = value['project_id'] + "_" + value['record'] + "_" + value['event'] + "_" + value['instance'];
            const slotLabel = value['slot'];
            let currentRow = slotLabel.substring(0, 1);
            if (previousRow != "" && currentRow != previousRow) {
                tableHTML += "</tr><tr>";
            }
            tableHTML += "<td><span class='slot_label'>" + slotLabel + "</span>";
            if (value['sample_id'] != "") {
                tableHTML += "<div id='sample_slot_" + slotID + "'>Part. ID: " + value['participant_id'] + "<br/>Samp. ID: " + value['sample_id'] + "<br/>Sample Type: " + value['planned_type'] + "<br/>Collect Date: " + value['collect_date'] + "<br/><input value='Checkout' type='button' id='sample_checkout_" + slotLabel + "' onclick='checkoutSample(\""+ajaxurl+"\",\""+project_id+"\",\""+event_id+"\",\""+value['sample_id']+"\",\""+slotID+"\",\""+slotLabel+"\",\"barcode_slot_"+value['project_id'] + "_" + value['record'] + "_" + value['event'] + "_" + (value['instance']+1)+"\");' /></div>";
            } else {
                tableHTML += "<div id='sample_slot_" + slotID + "'><span class='scan_barcode'><label for='barcode_slot_" + slotID + "'>Scan Barcode:</label><input class='barcode_text' type='text' id='barcode_slot_" + slotID + "' oninput='saveSample(\""+ajaxurl+"\",\""+project_id+"\",\""+event_id+"\",this.value,\"" + slotID + "\",\"" + slotLabel + "\",\"sample_issue_\",\"container_select\");loadSample(\""+ajaxurl+"\",\""+project_id+"\",\""+event_id+"\",this.value,\"" + slotID + "\",\"" + slotLabel + "\",\"barcode_slot_"+value['project_id'] + "_" + value['record'] + "_" + value['event'] + "_" + (value['instance']+1)+"\");' /></span></div>";
            }
            tableHTML += "</td>";
            previousRow = currentRow;
        }
        tableHTML += "</tr></table>";
        $('#' + table_id).html('').append(tableHTML);
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
            for (const key in resultData) {
                if (key == "") continue;
                sampleTable = "<table><tr><td>Sample ID</td><td>" + barcode + "</td></tr>";
                let sampleData = resultData[key];
                sampleTable += "<tr><td>Participant ID</td><td>" + sampleData['participant_id'] + "</td></tr>" +
                    "<tr><td>Collection Date</td><td>" + sampleData['collect_date'] + "</td></tr>" +
                    "<tr><td><h5>Expected Type</h5><br/>" + sampleData['planned_type'] + "</td><td><h5>EDC Type</h5><br/>" + sampleData['actual_type'] + "</td></tr>" +
                    "<tr><td><h5>Expected Collect Event</h5><br/>" + sampleData['planned_collect'] + "</td><td><h5>EDC Collect Event</h5><br/>" + sampleData['actual_collect'] + "</td></tr><tr><td>Issues</td><td><span><input id='sample_issue_1' type='checkbox' value='1' /><label for='sample_issue_1'>Empty</label></span><br/><span><input id='sample_issue_2' type='checkbox' value='2' /><label for='sample_issue_2'>Wrong Sample Type</label></span><br/><span><input id='sample_issue_3' type='checkbox' value='3' /><label for='sample_issue_3'>Sample Missing</label></span><br/><span><input id='sample_issue_4' type='checkbox' value='4' /><label for='sample_issue_4'>Damaged Sample</label></span><br/><span><input id='sample_issue_5' type='checkbox' value='5' /><label for='sample_issue_5'>Damaged Tube</label></span></td></tr><tr><td colspan='2'><label for='sample_issue_other'>Other Notes</label><textarea id='sample_issue_other' name='sample_issue_other'></textarea></td></tr>";
                sampleTable += "<tr><td colspan='2' style='text-align:center;'><input type='button' onclick='saveSample(\""+ajaxurl+"\",\""+project_id+"\",\""+event_id+"\",\"" + barcode + "\",\"" + parent_id + "\",\"" + slot_label + "\",\"sample_issue_\",\"container_select\");$(\"#sample_info_container\").css(\"display\",\"none\");' value='Save Sample' /></td></tr>";
                $('#' + parent_id).html("Part. ID: " + sampleData['participant_id'] + "<br/>Samp. ID: " + sampleData['sample_id'] + "<br/>Sample Type: " + sampleData['planned_type'] + "<br/>Collect Date: " + sampleData['collect_date']+ "<br/><input value='Checkout' type='button' id='sample_checkout_" + slot_label + "' onclick='checkoutSample(\""+ajaxurl+"\",\""+project_id+"\",\""+event_id+"\",\""+barcode+"\",\""+slot_id+"\",\""+slot_label+"\",\""+input_next+"\");' />");

                sampleTable += "</table>";
                $('#'+input_next).focus();
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
    let container = $('#' + container_id + ' option:selected').text();
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
            if (result['stored']) {
                $('#sample_row_' + barcode).css('background-color', 'lightgreen').find('td:eq(1)').html('Stored');
                $('#sample_row_' + barcode).find('td:eq(2)').html(container + '<br/>' + slot_label);
            } else {
                $('#sample_row_' + barcode).css('background-color', 'lightgreen').find('td:eq(1)').html('');
                $('#sample_row_' + barcode).find('td:eq(2)').html('');
            }
            if (result['discreps'] != "") {
                $('#sample_row_' + barcode).css('background-color', 'pink').find('td:eq(1)').html(result['discreps']);
                $('#sample_row_' + barcode).find('td:eq(2)').html(container + '<br/>' + slot_label);
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