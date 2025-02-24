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

window.loadProjectInfo = function(index, project_id, current_field, current_operator, current_value) {
    let data = {
        'project_id': project_id,
        'current_field': current_field
    }
    module.ajax('project-info', data)
        .then(function (response) {
            console.log(response);
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

