// путь к бэк-энду
const baseURL = 'https://library.istu.edu/Jsoner.php'

const resultContainer = document.getElementById ('result-container')
const busyIndicator   = document.getElementById ('busy-indicator')
const errorIndicator  = document.getElementById ('error-indicator')
const errorMessage    = document.getElementById ('error-message')
const inputRows =
[
    {
        select: document.getElementById ('select1'),
        input: document.getElementById ('input1'),
        check: document.getElementById ('check1')
    },

    {
        select: document.getElementById ('select2'),
        input: document.getElementById ('input2'),
        check: document.getElementById ('check2')
    },

    {
        select: document.getElementById ('select3'),
        input: document.getElementById ('input3'),
        check: document.getElementById ('check3')
    },
]

function show (element, state) {
    element.style.display = state ? "initial" : "none"
}

function showBusy() {
    show (busyIndicator, true)
}

function hideBusy() {
    show (busyIndicator, false)
}

function showError (message) {
    errorMessage.innerText = message
    show (errorIndicator, true)
}

function hideError() {
    show (errorIndicator, false)
}

function buildTerm (row) {
    if (row.input.value) {
        return '"' + row.select.value + row.input.value + (row.check.checked ? '$' : '') + '"'
    }

    return ''
}

function buildExpression() {
    let expression = ''
    for (const row of inputRows) {
        let term = buildTerm (row)
        if (term) {
            expression = expression ? (expression + ' * ' + term) : term
        }
    }

    if (!expression) {
        return ''
    }

    const database = 'ZIMA'
    const format = '@brief'
    const result = baseURL + '?op=search_format&db=' +  database + '&expr=' + encodeURIComponent (expression) + '&format=' + format
    console.log (result)

    return result
}

function handleSuccess (data) {
    const documents = data.sort()
    // console.log ('Найдено: ' + documents.length)

    if (documents.length === 0) {
        showError ('Не найдено ни одного документа, удовлетворяющего заданным условиям')
        return
    }

    for (const description of documents) {
        const item = document.createElement ('li')
        item.classList.add ('found-card')
        item.innerHTML = description
        resultContainer.appendChild (item)
    }
}

function handleSubmit() {
    hideError()
    resultContainer.innerHTML = ''
    const url = buildExpression()
    if (!url) {
        return false
    }

    showBusy()
    axios.get (url)
        .then (function (response) {
            handleSuccess (response.data)
            hideBusy()
        })
        .catch (function (error) {
            console.log (error)
            hideBusy()
            showError (error.message)
        })

    return false
}
