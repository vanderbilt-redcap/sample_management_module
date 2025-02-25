const module = ExternalModules.Vanderbilt.SampleManagementModule;

let reportFieldList = [];
let reportOptionList = [];
let reportProjectList = [];

let reportOperators = {
        0: '<',
        1: '>',
        2: '=',
        3: '>=',
        4: '<=',
        5: '<>'
}

window.loadProjectInfo = function(type, index, project_id, current_field, current_operator, current_value) {
    let data = {
        'project_id': project_id,
        'current_field': current_field
    }
    module.ajax('project-info', data)
        .then(function (response) {
            let fieldList = JSON.parse(response);

            switch (type) {
                case 'first_field':
                    addOptionsToSelect('first_link_field_list_' + index, fieldList, '');
                    break;
                case 'second_field':
                    addOptionsToSelect('second_link_field_list_' + index, fieldList, '');
                    break;
                case 'column_field':
                    break;
                case 'filter_field':
                    break;
                default:
                    console.log('Things went wrong');
                    break;
            }
        })
        .catch(function (err) {
            console.log(err);
        })
}

window.newDivBlock = function(parent_div,block_class) {
    let blocks = document.querySelectorAll('.'+block_class);
    let n = 0;
    if (blocks.length > 0) {
        let new_index = blocks.length;
        let block = blocks[0].cloneNode(true);
        while (n < block.childElementCount) {
            let splits = block.children[n].id.split('_');
            splits[splits.length-1] = new_index;
            block.children[n].id = splits.join('_');
            n++;
        }
        $('#'+parent_div).append(block);
    }
    else {
        console.log('no items');
    }
}

window.addOptionsToSelect = function(select_id,option_list,chosen_value) {
    let selectElement = document.getElementById(select_id);
    selectElement.options.length = 0;
    selectElement.add(createSelectElement('',''));

    for (let key in option_list) {
        selectElement.add(createSelectElement(key,key,(key == chosen_value)));
    }
}

window.createSelectElement = function(key,value,selected = false) {
    let optionElement = document.createElement('option');
    optionElement.value = key;
    optionElement.text = value;
    optionElement.selected = selected;
    return optionElement;
}
