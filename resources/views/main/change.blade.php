@extends('layout')
@section('item', 'link_7')
@section('content')

    <script> function ajax_settings(url, method, data){
            return {
                "url": url,
                "method": method,
                "timeout": 0,
                "headers": {"Content-Type": "application/json",},
                "data": data,
            }
        }</script>

    <div class="p-4 mx-1 mt-1 bg-white rounded py-3">

        @include('div.TopServicePartner')

        <form class="mt-3" action="" method="post">
        @csrf <!-- {{ csrf_field() }} -->
            <div class="row">

                <div id="message_good" class="mt-2 alert alert-success alert-dismissible fade show" style="display: none">
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>

                <div id="message" class="mt-2 alert alert-danger fade show " style="display: none">
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>

                <div id="info" class="mt-2 alert alert-secondary fade show " style="display: none">
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>

            </div>

            <hr>

            <div class='text-black text-center' >
                <div class="row ">
                    <div onclick="activate_btn('XReport')" class="col-2 btn btn-outline-dark textHover"> Получить X-Отчёт </div>
                    <div class="col-2"></div>
                    <div onclick="activate_btn('cash')" class="col-4 btn btn-outline-dark textHover"> Внесение/Изъятие </div>
                    <div class="col-2"></div>
                    <div onclick="activate_btn('ZReport')" class="col-2 btn btn-outline-dark textHover"> Получить Z-Отчёт </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        let accountId = '{{ $accountId }}'
        let message = @json($message);

        window.document.getElementById('info').innerText = message
        window.document.getElementById('info').style.display = "block"

        NAME_HEADER_TOP_SERVICE("Смена")

        function Report(Params){
            window.document.getElementById('Print').innerText = ''
            window.document.getElementById('message').style.display = 'none'

            let url = "{{ Config::get("Global")['url'] }}" + "kassa/"+Params+"/" +accountId

            let settings = ajax_settings(url, "GET", []);
            console.log(url + ' settings ↓ ')
            console.log(settings)
            $.ajax(settings).done(function (json) {
                console.log(url + ' response ↓ ')
                console.log(json)

                if (json.statusCode == 200){
                    $('#Print').append(json.Data.html)
                } else {
                    window.document.getElementById('message').style.display = 'block'
                    window.document.getElementById('message').innerText = json.message
                }

            })
        }

        function saveValCash(){
            let url = "{{ Config::get("Global")['url'] }}" + "kassa/MoneyOperation/" +accountId
            let data = {
                OperationType: window.document.getElementById('operations').value,
                Sum: window.document.getElementById('inputSum').value,
            };

            let settings = ajax_settings(url, "GET", data);
            console.log(url + ' settings ↓ ')
            console.log(settings)
            $.ajax(settings).done(function (json) {
                console.log(url + ' response ↓ ')
                console.log(json)

                if (json.statusCode == 200){
                    console.log('true')
                    let message_good = window.document.getElementById('message_good');
                    message_good.style.display = 'block'
                    message_good.innerText = json.message
                    closeModal('cash')
                } else {
                    console.log('false')
                    window.document.getElementById('message').style.display = 'block'
                    window.document.getElementById('message').innerText = json.message
                    closeModal('cash')
                }

            })
        }


    </script>

    <div class="modal fade" id="cash" tabindex="-1"  role="dialog" aria-labelledby="cashTitle" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cashTitle">Внесение</h5>
                    <div class="close" data-dismiss="modal" aria-label="Close" style="cursor: pointer;"><i onclick="closeModal('cash')" class="fa-regular fa-circle-xmark"></i></div>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <label for="operations" class="col-5 col-form-label"> Выберите операцию </label>
                        <div class="col-7">
                            <select id="operations" name="operations" class="form-select text-black" onchange="valueCash(this.value)">
                                <option value="1"> Внесение </option>
                                <option value="0"> Изъятие </option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <label for="operations" class="col-5 col-form-label">
                            <span id="inputGroupText" class="p-2 text-white bg-success rounded">Введите сумму </span>
                        </label>
                        <div class="col-7 input-group mt-1">
                            <input id="inputSum" name="inputSum" onkeypress="return isNumber(event)" type="text" class="form-control" aria-label="">
                            <div class="input-group-append">
                                <span class="input-group-text">.00</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button onclick="closeModal('cash')" type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                    <button onclick="saveValCash()" type="button" class="btn btn-primary">Сохранить</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="Report" tabindex="-1"  role="dialog" aria-labelledby="cashTitle" aria-hidden="true">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"> Отчёт </h5>
                    <div class="close" data-dismiss="modal" aria-label="Close" style="cursor: pointer;"><i onclick="closeModal('Report')" class="fa-regular fa-circle-xmark"></i></div>
                </div>
                <div id="Print" class="modal-body divPrint" style="font-size: 14px">
                </div>
                <div class="modal-footer">
                    <button onclick="closeModal('XReport')" type="button" class="btn btn-secondary" data-dismiss="modal">Закрыть</button>
                    <button onclick="PrintDiv('Print');" type="button" class="btn btn-success" data-dismiss="modal">Распечатать</button>
                    <a target="_blank" href="{{ Config::get("Global")['kassa'] }}" class="btn btn-primary">Открыть в Wipon</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function PrintDiv(divName) {
            var printContents = document.getElementById(divName).innerHTML;
            var printContentsBODY = "<style> body { font-family: 'Calibri', 'Arial', sans-serif !important; } </style>"
            var printContentsCSS = " <style> div{ font-size: 12px !important; } table tr td{ font-size: 12px !important; padding: 0 !important; margin: 0 !important; } html, body { margin: 0px; padding: 0px; border: 0px; width: 100%; height: 100%; font-size: 13px !important; } iframe { width: 200px; height: 200px; margin: 0px; padding: 0px; border: 0px; display: block; } </style>";
            w = window.open();

            w.document.write(printContents + printContentsCSS+ printContentsBODY);
            w.document.write('<scr' + 'ipt type="text/javascript">' + 'window.onload = function() { window.print(); window.close(); };' + '</sc' + 'ript>');

            w.document.close(); // necessary for IE >= 10
            w.focus(); // necessary for IE >= 10

            return true;
        }

        function isNumber(evt){
            var charCode = (evt.which) ? evt.which : event.keyCode
            if (charCode == 46){
                var inputValue = $("#card").val();
                var count = (inputValue.match(/'.'/g) || []).length;
                if(count<1){
                    if (inputValue.indexOf('.') < 1){
                        return true;
                    }
                    return false;
                }else{
                    return false;
                }
            }
            if (charCode != 46 && charCode > 31 && (charCode < 48 || charCode > 57)){
                return false;
            }
            return true;
        }

        function activate_btn(params){
            if (params == 'cash'){
                $('#cash').modal('show')
                window.document.getElementById('inputSum').value = 0
            }
            if (params == 'XReport'){
                Report("XReport")
                $('#Report').modal('show')
            }
            if (params == 'ZReport'){
                Report("ZReport")
                $('#Report').modal('show')
            }
        }

        function closeModal(params) {
            if (params == 'cash'){
                $('#cash').modal('hide')
            }
            if (params == 'Report'){
                $('#Report').modal('hide')
            }
        }

        function valueCash(val){

            if (val == 0 ) {
                window.document.getElementById('cashTitle').innerText = 'Внесение'
                document.getElementById('inputGroupText').classList.add('bg-success')
                document.getElementById('inputGroupText').classList.remove('bg-danger')
            }
            if (val == 1) {
                window.document.getElementById('cashTitle').innerText = 'Изъятие'
                document.getElementById('inputGroupText').classList.add('bg-danger')
                document.getElementById('inputGroupText').classList.remove('bg-success')
            }
        }

    </script>
@endsection

