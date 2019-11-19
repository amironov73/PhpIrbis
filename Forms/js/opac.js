const serviceUrl = "http://localhost:63342/PhpIrbis/Source/Jsoner.php?";

const dataRoot = {
    selectedDb: null,
    databases: [],
    selectedScenario: null,
    scenarios: [],
    searchValue: '',
    trimValue: true,
    foundBooks: [],
    foxVisible: false,
};

const app = new Vue({
    el: '#app',
    data: dataRoot,
    computed: {
        searchExpression: function () {
            return dataRoot.selectedScenario + dataRoot.searchValue
                + (dataRoot.trimValue ? '$' : '');
        },
    }
});

function loadScenarios() {
    const url = serviceUrl + 'op=scenarios';
    app.foxVisible = true;
    axios.get(url).then(function (response) {
        app.scenarios = response.data;
        app.selectedScenario = app.scenarios[0].prefix;
    }).finally(function () {
        app.foxVisible = false;
    });
}

function loadDatabases() {
    const url = serviceUrl + 'op=list_db';
    app.foxVisible = true;
    axios.get(url).then(function (response) {
        app.databases = response.data;
        app.selectedDb = app.databases[0].name;
        app.foxVisible = false;
        loadScenarios();
    });
}

function searchBooks() {
    const url = serviceUrl + 'op=search_format&db=' + app.selectedDb
        + '&expr=' + app.searchExpression
        + '&format=@brief';
    app.foxVisible = true;
    app.foundBooks = [];
    axios.get(url).then(function (response) {
        app.foundBooks = response.data;
        if (app.foundBooks.length === 0) {
            app.foundBooks = ['К сожалению, ничего не найдено'];
        }
    }).finally(function () {
        app.foxVisible = false;
    });
    return false;
}

loadDatabases();
