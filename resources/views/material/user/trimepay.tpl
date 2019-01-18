<div class="row">

    <div class="col-lg-6 col-md-6">
        <p class="card-heading">TrimePay 充值</p>
        <div class="form-group form-group-label">
            <label class="floating-label" for="amount">金额</label>
            <input class="form-control" id="amount" type="text" >
        </div>
    </div>
    <div class="col-lg-6 col-md-6">
        <p class="h5 margin-top-sm text-black-hint" id="qrarea"></p>
    </div>
</div>

<div class="card-action">
    <div class="card-action-btn pull-left">
        <button class="btn btn-flat waves-attach" id="btnSubmit" name="type" onclick="pay('Alipay')">
            <svg class="icon" style="width: 40px; height: 40px;vertical-align: middle;fill: currentColor;overflow: hidden;" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="557"><path d="M991.67 689.448v-472.296c0-101.81-82.623-184.433-184.493-184.433h-590.414c-101.81 0-184.433 82.623-184.433 184.433v590.414c0 101.81 82.563 184.433 184.433 184.433h590.414c90.717 0 166.206-65.595 181.615-151.876-48.926-21.165-260.941-112.722-371.385-165.427-84.062 101.81-172.082 162.908-304.771 162.908s-221.248-81.724-210.636-181.735c7.015-65.655 52.044-172.981 247.629-154.574 103.069 9.653 150.257 28.9 234.319 56.661 21.765-39.873 39.813-83.763 53.543-130.47h-372.764v-36.935h184.433v-66.314h-224.965v-40.652h224.965v-95.694c0 0 2.039-14.99 18.587-14.99h92.216v110.684h239.835v40.652h-239.835v66.314h195.646c-17.988 73.209-45.269 140.483-79.446 199.243 56.841 20.566 315.503 99.652 315.503 99.652v0 0zM297.947 774.29c-140.183 0-162.368-88.499-154.933-125.494 7.375-36.815 47.967-84.842 125.914-84.842 89.578 0 169.803 22.904 266.097 69.792-67.633 88.079-150.736 140.543-237.077 140.543v0z" p-id="558"></path></svg>
        </button>
        <button class="btn btn-flat waves-attach" id="btnSubmit" name="type" onclick="pay('WEPAY_JSAPI')">
            <svg class="icon" style="width: 40px; height: 40px;vertical-align: middle;fill: currentColor;overflow: hidden;" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="4107"><path d="M294.033 294.964c-10.619 0-21.179 4.32-28.618 11.819-7.44 7.5-11.819 17.939-11.819 28.618 0 10.559 4.32 21.119 11.819 28.618 7.5 7.38 17.999 11.759 28.618 11.759 10.559 0 21.119-4.38 28.558-11.819 7.5-7.5 11.819-18.059 11.819-28.558 0-10.679-4.32-21.119-11.819-28.618-7.44-7.5-17.999-11.819-28.558-11.819zM600.074 512.751c-8.279 0-16.499 3.42-22.379 9.239s-9.299 14.039-9.299 22.379 3.42 16.499 9.239 22.379 14.159 9.239 22.439 9.239 16.499-3.42 22.379-9.239 9.239-14.039 9.239-22.379-3.42-16.499-9.239-22.379-14.159-9.239-22.379-9.239zM500.241 375.779c10.559 0 21.119-4.38 28.618-11.819 7.44-7.5 11.759-18.059 11.759-28.618 0-10.679-4.32-21.119-11.759-28.618-7.559-7.44-18.119-11.759-28.618-11.759-10.619 0-21.119 4.32-28.618 11.819s-11.819 17.939-11.819 28.618c0 10.559 4.32 21.119 11.819 28.618 7.5 7.38 17.999 11.759 28.618 11.759zM836.34 32h-648.681c-85.915 0-155.511 69.836-155.511 156.051v647.902c0 86.214 69.596 156.051 155.511 156.051h648.741c85.915 0 155.511-69.836 155.511-156.051v-647.902c-0.060-86.214-69.657-156.051-155.571-156.051zM392.308 675.162c-36.298 0-65.575-7.44-101.933-14.579l-101.693 50.997 29.098-87.534c-72.835-50.937-116.393-116.513-116.393-196.368 0-138.532 131.033-247.485 290.982-247.485 143.092 0 268.423 87.054 293.562 204.348-9.239-1.081-18.599-1.74-28.018-1.74-138.233 0-247.365 103.193-247.365 230.266 0 21.239 3.3 41.517 8.939 61.015-8.999 0.601-18.059 1.081-27.178 1.081zM821.461 777.095l21.899 72.655-79.795-43.737c-29.098 7.321-58.375 14.639-87.354 14.639-138.412 0-247.425-94.614-247.425-211.187 0-116.333 109.013-211.187 247.425-211.187 130.793 0 247.185 94.794 247.185 211.187 0 65.696-43.497 123.772-101.933 167.63zM758.585 512.751c-8.279 0-16.499 3.42-22.379 9.239s-9.239 14.039-9.239 22.379 3.42 16.499 9.239 22.379 14.099 9.239 22.379 9.239 16.499-3.42 22.379-9.239 9.239-14.039 9.239-22.379-3.42-16.499-9.239-22.379-14.099-9.239-22.379-9.239z" p-id="4108"></path></svg>
        </button>
    </div>
</div>

<div aria-hidden="true" class="modal modal-va-middle fade" id="readytopay" role="dialog" tabindex="-1">
    <div class="modal-dialog modal-xs">
        <div class="modal-content">
            <div class="modal-heading">
                <a class="modal-close" data-dismiss="modal">×</a>
                <h2 class="modal-title">正在连接支付网关</h2>
            </div>
            <div class="modal-inner">
                <p id="title">感谢您对我们的支持，请耐心等待</p>
            </div>
        </div>
    </div>
</div>


<script src="//cdn.bootcss.com/jquery.qrcode/1.0/jquery.qrcode.min.js"></script>
<script>
    var pid = 0;

    function pay(type){
        if (type==='Alipay'){
            if(/Android|webOS|iPhone|iPod|BlackBerry/i.test(navigator.userAgent)) {
                type = 'ALIPAY_WAP';
            } else {
                type = 'ALIPAY_WEB';
            }
        }

        var price = parseFloat($("#amount").val());

        console.log("将要使用 "+ type + " 充值" + price + "元");
        if (isNaN(price)) {
            $("#readytopay").modal('hide');
            $("#result").modal();
            $("#msg").html("非法的金额!");
            return;
        }
        $('#readytopay').modal();
        $("#readytopay").on('shown.bs.modal', function () {
            $.ajax({
                'url': "/user/payment/purchase",
                'data': {
                    'price': price,
                    'type': type,
                },
                'dataType': 'json',
                'type': "POST",
                success: function (data) {
                    if (data.code == 0) {
                        console.log(data);
                        $("#readytopay").modal('hide');
                        if(type === 'ALIPAY_WAP' || type ==='ALIPAY_WEB'){
                            window.location.href = data.data;
                        } else {
                            pid = data.pid;
                            $("#qrarea").html('<div class="text-center"><p>使用微信扫描二维码支付</p><div align="center" id="qrcode" style="padding-top:10px;"></div><p>充值完毕后会自动跳转</p></div>');
                            $('#qrcode').qrcode({
                                text: encodeURI(data.data),
                                width: 200,
                                height: 200
                            });
                            tid = setTimeout(f, 1000); //循环调用触发setTimeout
                        }
                    } else {
                        $("#result").modal();
                        $("#msg").html(data.msg);
                        console.log(data);
                    }
                }
            });
        });
    }

    function f(){
        $.ajax({
            type: "POST",
            url: "/payment/status",
            dataType: "json",
            data: {
                pid:pid
            },
            success: function (data) {
                if (data.result) {
                    console.log(data);
                    $("#result").modal();
                    $("#msg").html("充值成功！");
                    window.setTimeout("location.href=window.location.href", {$config['jump_delay']});
                }
            },
            error: function (jqXHR) {
                console.log(jqXHR);
            }
        });
        tid = setTimeout(f, 1000); //循环调用触发setTimeout
    }

</script>
