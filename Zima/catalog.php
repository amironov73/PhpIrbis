<?php

include ('head.html');
include ('navbar.html');

?>

<main class="page">
    <section class="clean-block features">
        <div class="container">
            <div class="block-heading" style="margin-top: -15pt">
                <h2 class="text-info">Онлайн-каталог проекта</h2>
            </div>
            <div class="row">

                <form class="col-lg-6 offset-lg-3 mt-3" autocomplete="off" onsubmit="return handleSubmit()">

                    <div class="input-group">
                        <select id="select1" class="btn btn-outline-primary">
                            <option value="A=" selected>Автор</option>
                            <option value="T=">Заглавие</option>
                            <option value="MR=">Кафедра</option>
                            <option value="K=">Ключевое слово</option>
                            <option value="G=">Год издания</option>
                            <option value="TJ=">Источник</option>
                            <option value="BBK=">ББК</option>
                        </select>
                        <input id="input1" type="text" class="form-control">
                        <div class="input-group-text">
                            <input id="check1" type="checkbox" class="form-check-input" checked
                                   data-bs-toggle="tooltip" data-bs-placement="top" title="усечение">
                        </div>
                    </div>

                    <div class="input-group">
                        <select id="select2" class="btn btn-outline-primary">
                            <option value="A=">Автор</option>
                            <option value="T=" selected>Заглавие</option>
                            <option value="MR=">Кафедра</option>
                            <option value="K=">Ключевое слово</option>
                            <option value="G=">Год издания</option>
                            <option value="TJ=">Источник</option>
                            <option value="BBK=">ББК</option>
                        </select>
                        <input id="input2" type="text" class="form-control">
                        <div class="input-group-text">
                            <input id="check2" type="checkbox" class="form-check-input" checked
                                   data-bs-toggle="tooltip" data-bs-placement="top" title="усечение">
                        </div>
                    </div>

                    <div class="input-group">
                        <select id="select3" class="btn btn-outline-primary">
                            <option value="A=">Автор</option>
                            <option value="T=">Заглавие</option>
                            <option value="MR=">Кафедра</option>
                            <option value="K=" selected>Ключевое слово</option>
                            <option value="G=">Год издания</option>
                            <option value="TJ=">Источник</option>
                            <option value="BBK=">ББК</option>
                        </select>
                        <input id="input3" type="text" class="form-control">
                        <div class="input-group-text">
                            <input id="check3" type="checkbox" class="form-check-input" checked
                                   data-bs-toggle="tooltip" data-bs-placement="top" title="усечение">
                        </div>
                    </div>

                    <div class="input-group mt-3 mb-3">
                        <button type="submit" class="btn btn-primary form-control">Поиск</button>
                        &nbsp;
                        <button type="reset" class="btn btn-outline-primary form-control">Сброс</button>
                    </div>

                </form>

                <div id="busy-indicator" class="text-center mb-5" style="display: none;">
                    <h2><img id="arctic-fox" src="images/arctic-fox.gif" class="w-75" alt="полярная лиса"></h2>
                </div>

                <div id="error-indicator" class="text-center text-danger mb-5" style="display: none;">
                    <h2 id="error-message">Произошла ошибка</h2>
                </div>

                <div class="mt-3 mb-3">
                    <ol id="result-container"></ol>
                </div>

            </div>

        </div>
    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="catalog.js"></script>

<?php

include ('footer.html');
