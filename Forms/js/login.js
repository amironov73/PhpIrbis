const dataRoot = {
    login: "",
    password: "",
    loginSuccess: "",
    loginMessage: "",
    foxVisible: false
};

const app = new Vue({
    el: '#app',
    data: dataRoot
});

function doLogin() {
    const url = gatekeeperUrl + 'op=info&login=' + app.login
        + '&password=' + app.password;
    app.loginSuccess = "";
    app.loginMessage = "";
    app.foxVisible = true;
    axios.get(url).then(function (response) {
        app.foxVisible = false;
        const success = response.data.success;
        app.loginSuccess = success ? "Успешно" : "Неуспешно";
        if (success) {
            $("#success1").removeClass("alert-warning").addClass("alert-info");
            $("#success2").removeClass("alert-warning").addClass("alert-info");
            const rdr = response.data.reader;
            app.loginMessage = rdr.name + ' (' + rdr.category + ')';
        }
        else {
            $("#success1").removeClass("alert-info").addClass("alert-warning");
            $("#success2").removeClass("alert-info").addClass("alert-warning");
            app.loginMessage = response.data.message;
        }
    });

    return false;
}