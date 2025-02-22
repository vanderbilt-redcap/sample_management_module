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

