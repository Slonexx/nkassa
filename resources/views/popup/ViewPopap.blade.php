@extends('popup.index')
@section('content')




    <div class="main-container">
        <div class="row gradient rounded p-2">
            <div class="col-2">
                <div class="mx-2"> <img src="{{ asset('integration.svg') }}" width="90%"  alt=""></div>
            </div>
            <div class="col-6 text-black " style="font-size: 22px; margin-top: 1.2rem !important;">
                <span id="nameObjectHeader"> Заказ покупателя № </span>
                <span id="numberOrder" class="text-black"></span>
            </div>
            <div class="col-3"></div>
        </div>
        <div id="message" class="mt-2 row" style="display:none;" >
            <div class="col-12">
                <div id="messageAlert" class=" mx-3 p-2 alert alert-danger text-center ">
                </div>
            </div>
        </div>
        <div id="messageGood" class="mt-2 row" style="display:none;" >
            <div class="col-12">
                <div id="messageGoodAlert" class=" mx-3 p-2 alert alert-success text-center ">
                </div>
            </div>
        </div>
        <div class="content-container">
            <div class=" rounded bg-white">
              <div class="row p-3">
                  <div class="divTable myTable">
                      <div class="divTableHeading">
                          <div class="divTableRow">

                              <div class="divTableHead text-black">№</div>
                              <div class="divTableHead text-black">Наименование</div>
                              <div class="divTableHead text-black">Кол-во</div>
                              <div class="divTableHead text-black">Ед. Изм.</div>
                              <div class="divTableHead text-black">Цена</div>
                              <div class="divTableHead text-black">НДС</div>
                              <div class="divTableHead text-black">Скидка</div>
                              <div class="divTableHead text-black">Сумма</div>
                              <div class="divTableHead text-black">Учитывать </div>
                              <div class="buttons-container-head mt-1"></div>

                          </div>
                      </div>
                      <div id="main" class="divTableBody">

                      </div>
                  </div>

                </div>
        </div>
        </div>
        <div class="buttons-container-head"></div>
        <div class="buttons-container">
            <div class="row">
                <div class="col-12 row">
                    <div class="col-3">
                        <div class="row">
                            <div class="col-5">
                                <div class="mx-1 mt-1 bg-warning p-1 rounded text-center">Тип оплаты</div>
                            </div>
                            <div class="col-7">
                                <select onchange="SelectorSum(this.value)" id="valueSelector" class="form-select">
                                    <option selected value="1">Наличными</option>
                                    <option value="2">Картой</option>
                                    <option value="3">Смешанная</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="row">
                            <div class="col-4"> <div id="Visibility_Cash" class="mx-2" style="display: none">
                                    <input id="cash" type="number" step="0.1" placeholder="Сумма наличных"  onkeypress="return isNumberKeyCash(event)"
                                           class="form-control float" required maxlength="255" value="">
                                </div> </div>
                            <div class="col-4"> <div id="Visibility_Card" class="mx-2" style="display: none">
                                    <input id="card" type="number" step="0.1"  placeholder="Сумма картой" onkeypress="return isNumberKeyCard(event)"
                                           class="form-control float" required maxlength="255" value="">
                                </div> </div>
                        </div>
                    </div>
                    <div class="col-1"></div>
                    <div class="col-2 d-flex justify-content-end">
                        <button onclick="PrintCheck()" id="ShowCheck" class="btn btn-success">Распечатать чек</button>
                    </div>
                </div>
                <div class="col-7 row mt-2">
                    <div class="row">
                        <div class="col-12 mx-2 ">
                            <div class="col-5 bg-info text-white p-1 rounded">
                                <span class="mx-2"> Итого: </span>
                                <span id="sum"></span>
                            </div>

                        </div>
                    </div>
                </div>
                <div class="col-3"></div>
                <div class="col-2 d-flex justify-content-end">
                    <button onclick="sendKKM('return')" id="refundCheck" class="btn btn-danger">возврат</button>
                    <button onclick="sendKKM('sell')" id="getKKM" class="mt-1 btn btn-success">Отправить в ККМ</button>
                </div>


            </div>
        </div>
    </div>


    <div id="downL" class="modal fade bd-example-modal-sm" data-bs-keyboard="false" data-bs-backdrop="static"
         tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"> <i class="fa-solid fa-circle-exclamation text-danger"></i>
                        Отправка
                    </h5>
                </div>
                <div class="modal-body text-center" style="background-color: #e5eff1">
                    <div class="row">
                        <img style="width: 100%" src="https://i.gifer.com/1uoA.gif" alt="">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="lDown" class="modal fade bd-example-modal-sm" data-bs-keyboard="false" data-bs-backdrop="static"
         tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"> <i class="fa-solid fa-circle-exclamation text-danger"></i>
                        Загрузка
                    </h5>
                </div>
                <div class="modal-body text-center" style="background-color: #e5eff1">
                    <div class="row">
                        <img style="width: 100%" src="https://i.gifer.com/1uoA.gif" alt="">
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div id="ReportRow" class="row" style="display: block">
        <div id="company_name" style="text-align: center"> </div>
        <div style="text-align: center">ИИН/БИН &nbsp; <span id="bin"></span> </div>
        <hr style="border:1px dashed black; margin-top: 0 !important; margin-bottom: 0.5rem;">
        <div style="text-align: center">  &nbsp; КАССА  &nbsp; <span id="posName"></span>  &nbsp; |  &nbsp; Смена &nbsp; <span id="pos_shift_number"></span> &nbsp; </div>
        <div style="text-align: center"> Порядковый номер чека &nbsp; <span id="sale_id"></span> </div>
        <div style="text-align: center"> &nbsp; <span id="cashier_name"></span> &nbsp; </div>
        <div style="text-align: left"> <span id="status_def"></span> &nbsp; </div>
        <hr style="border:1px dashed black; margin-top: 0 !important; margin-bottom: 0.5rem;">
        <div>
            <table role="presentation" style="width: 100%; ">
            <tbody id="positions" role="presentation" style="width: 100%; ">

            </tbody>
            </table>
        </div>
        <hr style="border:1px dashed black; margin-top: 0 !important; margin-bottom: 0.5rem;">
        <table role="presentation" style="width: 100%; ">
            <tbody>

            <tr style="padding: 0;">
                <td style="padding: 0; text-align: right" colspan="5"><b>ИТОГО</b></td>
                <td id="sum" style="padding: 0; text-align: right;" colspan="7"> 0 </td>
            </tr>
            <tr style="padding: 0;">
                <td style="padding: 0; text-align: right" colspan="5"><b>Наличные</b></td>
                <td id="payment_method_id_0_Sum" style="padding: 0; text-align: right;" colspan="7"> 0 </td>
            </tr>
            <tr style="padding: 0;">
                <td style="padding: 0; text-align: right" colspan="5"><b>Карта</b></td>
                <td id="payment_method_id_1_Sum" style="padding: 0; text-align: right;" colspan="7"> 0 </td>
            </tr>
            <tr style="padding: 0;">
                <td style="padding: 0; text-align: right" colspan="5"><b>Сумма сдачи</b></td>
                <td id="change" style="padding: 0; text-align: right;" colspan="7"> 0 </td>
            </tr>

            </tbody>
        </table>
        <hr style="border:1px dashed black; margin-top: 0 !important; margin-bottom: 0.5rem;">
        <div style="text-align: center">Время: &nbsp; <span id="created_at"></span> </div>
        <div style="text-align: center">Адрес: &nbsp; <span id="address"></span> </div>
        <div style="text-align: center">Оператор фискальных данных: &nbsp; <span id="ofd_connection"></span> </div>
        <div style="text-align: center">Для проверки чека зайдите на сайт consumer.oofd.kz </div>

        <hr style="border:1px dashed black; margin-top: 0.5rem !important; margin-bottom: 0.5rem;">
        <div style="text-align: center"> Фискальный чек </div>
        <div style="text-align: center">ФП: &nbsp; <span id="receipt_number"></span> </div>
        <div style="text-align: center">ЗНМ: &nbsp; <span id="factory_number"></span> </div>
        <div style="text-align: center">РНМ: &nbsp; <span id="registration_number"></span> </div>
        <div id="ofd_qr" style="text-align: center">QR код не доступен </div>
        <div style="text-align: center">NURKASSA.KZ </div>

    </div>


    @include('popup.script_popup_app')
    @include('popup.style_popup_app')

    <script>

        const url = "{{Config::get("Global")['url']}}" + 'Popup/'

        let object_Id = ''
        let accountId = ''
        let entity_type = ''
        let id_ticket = ''
        let html = ''

        let payment_type = ''
        let products_length = ''


        /*let receivedMessage = {
            "name":"OpenPopup",
            "messageId":1,
            "popupName":"fiscalizationPopup",
            "popupParameters":
                {
                    "object_Id":"a82f21d9-d901-11ed-0a80-0068000607bb",
                    "accountId":"1dd5bd55-d141-11ec-0a80-055600047495",
                    "entity_type":"customerorder",
                }
        };*/

        window.addEventListener("message", function(event) {
        let receivedMessage = event.data

        newPopup()

        if (receivedMessage.name === 'OpenPopup') {
            object_Id = receivedMessage.popupParameters.object_Id;
            accountId = receivedMessage.popupParameters.accountId;
            entity_type = receivedMessage.popupParameters.entity_type;

            //receivedMessage = 0


            if (entity_type === 'customerorder'){
                window.document.getElementById('nameObjectHeader').innerText = "Заказ покупателя "
            }
            if (entity_type === 'salesreturn'){
                window.document.getElementById('nameObjectHeader').innerText = "Возврат покупателя "
            }
            if (entity_type === 'demand'){
                window.document.getElementById('nameObjectHeader').innerText = "Отгрузка "
            }



            let data = { object_Id: object_Id, accountId: accountId, };

            let settings = ajax_settings(url+entity_type+"/show", "GET", data);
            console.log(url+entity_type+"/show" + ' settings ↓ ')
            console.log(settings)

            $.ajax(settings).done(function (json) {
                console.log(url+entity_type+"/show"  + ' response ↓ ')
                console.log(json)

                if (json.statusCode === 500) {
                    window.document.getElementById("messageAlert").innerText = json.message
                    window.document.getElementById("message").style.display = "block"
                } else {
                    window.document.getElementById("numberOrder").innerHTML = json.name
                    payment_type = json.application.payment_type
                    if (payment_type == null || payment_type == undefined) {
                        window.document.getElementById("messageAlert").innerText = "Отсутствуют настройки приложения "
                        window.document.getElementById("message").style.display = "block"
                    } else {
                        id_ticket = json.attributes.ticket_id
                        products_length = json.products.length

                        setProducts(json.products)
                        payment_type_on_set_option(payment_type, window.document.getElementById("sum").innerHTML)

                        if (json.attributes != null){
                            if (json.attributes.ticket_id != null){
                                window.document.getElementById("refundCheck").style.display = "block";
                                window.document.getElementById("ShowCheck").style.display = "block";
                            } else {
                                window.document.getElementById("getKKM").style.display = "block";
                            }
                        } else  window.document.getElementById("getKKM").style.display = "block";

                    }



                }

            })
        }
         });



        function sendKKM(pay_type){
            let button_hide = ''
            if (pay_type === 'return') button_hide = 'refundCheck'
            if (pay_type === 'sell') button_hide = 'getKKM'

            window.document.getElementById(button_hide).style.display = "none"
            let modalShowHide = 'show'

            let total = window.document.getElementById('sum').innerText
            let money_card = window.document.getElementById('card').value
            let money_cash = window.document.getElementById('cash').value
            let SelectorInfo = document.getElementById('valueSelector')
            let option = SelectorInfo.options[SelectorInfo.selectedIndex]

            let error_what = option_value_error_fu(option.value, money_cash, money_card)
            if (error_what === true){
                modalShowHide = 'hide'
            }

            if (total-0.01 <= money_card+money_cash){
                let url = "{{Config::get("Global")['url']}}" + 'Popup/'+entity_type+"/send"

                if (modalShowHide === 'show'){
                    $('#downL').modal('toggle')
                    let products = []
                    for (let i = 0; i < products_length; i++) {
                        if (window.document.getElementById(i).style.display !== 'none') {
                            products[i] = {
                                id:window.document.getElementById('productId_'+i).innerText,
                                name:window.document.getElementById('productName_'+i).innerText,
                                quantity:window.document.getElementById('productQuantity_'+i).innerText,
                                UOM:window.document.getElementById('productIDUOM_'+i).innerText,
                                price:window.document.getElementById('productPrice_'+i).innerText,
                                is_nds:window.document.getElementById('productVat_'+i).innerText,
                                discount:window.document.getElementById('productDiscount_'+i).innerText
                            }
                        }
                    }

                    let data =  {
                        "accountId": accountId,
                        "object_Id": object_Id,
                        "entity_type": entity_type,

                        "money_card": money_card,
                        "money_cash": money_cash,

                        "pay_type": pay_type,
                        "total": total,

                        "position": JSON.stringify(products),
                    }
                    console.log(url + ' data ↓ ')
                    console.log(data)

                    $.ajax({
                        url: url,
                        method: 'post',
                        dataType: 'json',
                        data: data,
                        success: function(json){
                            $('#downL').modal('hide')
                            console.log(url + ' response ↓ ')
                            console.log(json)

                            if (json.status === 'Ticket created'){
                                window.document.getElementById("messageGoodAlert").innerText = "Чек создан, пожалуйста закройте документ без сохранения!";
                                window.document.getElementById("messageGood").style.display = "block";
                                window.document.getElementById("ShowCheck").style.display = "block";
                                html = json.postTicket.data.preview_link

                                modalShowHide = 'hide';
                            } else {
                                window.document.getElementById('message').style.display = "block";
                                window.document.getElementById(button_hide).style.display = "block";
                                if (json.hasOwnProperty('errors'))window.document.getElementById('messageAlert').innerText = JSON.stringify(json.errors)
                                else window.document.getElementById('messageAlert').innerText = "Ошибка: " + JSON.stringify(json)

                                modalShowHide = 'hide';
                            }
                        }
                    });
                    modalShowHide = 'hide';
                }
                else window.document.getElementById(button_hide).style.display = "block"
            } else {
                window.document.getElementById('messageAlert').innerText = 'Введите сумму больше !'
                window.document.getElementById('message').style.display = "block"
                window.document.getElementById(button_hide).style.display = "block";
                modalShowHide = 'hide'
            }
        }



        /*      html = json.postTicket.html

                             //$('#main').append(value)
                             window.document.getElementById('company_name').innerText =  json.postTicket.data.params.company_name
                             window.document.getElementById('bin').innerText =  json.postTicket.data.params.bin
                             window.document.getElementById('posName').innerText =  json.postTicket.data.params.pos.name
                             window.document.getElementById('pos_shift_number').innerText =  json.postTicket.data.params.pos_shift_number
                             window.document.getElementById('sale_id').innerText = "№"+json.postTicket.data.params.sale_id
                             window.document.getElementById('cashier_name').innerText =  json.postTicket.data.params.cashier_name
                             let item = json.postTicket.data.params.sale_items
                             console.log( item.length )
                             for (let index = 0; index> item.length -1; index++){
                                 let value = '<tr style="vertical-align:top;"> <td colspan="12"> <nobr> '+index+'.</nobr>  '+item[index].name+' </td> </tr>'

                                 value = value + '<tr style="vertical-align:top;"> <td colspan="8">  '+item[index].price+'*'+item[index].quantity+'ШТ'+' </td> <td colspan="4">  '+item[index].cost+' </td> </tr>'
                                 $('#positions').append(value)
                                 if (item[index].discount_def > 0) {
                                     value = value + '<tr style="vertical-align:top;"> <td colspan="3"> Скидка </td> <td colspan="9">  '+item[index].sum+' </td> </tr>'
                                     $('#positions').append(value)
                                 }
                                 value = value + '<tr style="vertical-align:top;"> <td colspan="3"> Стоимость </td> <td colspan="9">  '+item[index].sum+' </td> </tr>'
                                 $('#positions').append(value)
                             }*/



    </script>
@endsection
