;
(function ($) {
    /*高级搜索*/
    $("#h-search").click(function () {
        $(".form-group.hide").removeClass('hide');
    })
    $('#close').click(function () {
            $(this).parents('.form-group').addClass('hide');
        })
        /*日期选择*/
    laydate.render({
        elem: '#date',
        range: true,
        change: function (value, date, endDate) {
            console.log(value); //得到日期生成的值，如：2017-08-18
            console.log(date); //得到日期时间对象：{year: 2017, month: 8, date: 18, hours: 0, minutes: 0, seconds: 0}
            console.log(endDate); //得结束的日期时间对象，开启范围选择（range: true）才会返回。对象成员同上。
        }
    });
    /*订单状态筛选*/
    $('#orderState').mouseenter(function () {
        $(this).addClass('deal-state-hover');
    }).mouseleave(function () {
        $(this).removeClass('deal-state-hover');
    });
    $('.state-list').on('click', 'li', function () {
        const state = $(this).attr("value");
        const text = $.trim($(this).text());
        $(this).find('a').addClass('curr');
        $(this).siblings('li').find('a').removeClass('curr');
        $(".state-txt").find('span:not(".blank")').text(text)
            // window.location.href=url+"?sta="+state;
    });
    /*checkbox选择*/
    /*点击全选按钮*/
    $("#allCheck").on("click", "input[type='checkbox']", function () {
        $(this).toggleClass("check");

        if ($(this).hasClass("check")) {
            $("input[type='checkbox']").prop("checked", true).addClass("check");

        } else {
            $("input[type='checkbox']").prop("checked", false).removeClass("check");
        }

    });
    /*点击单个订单按钮*/
    $(".tr-th").on("click", "input[type='checkbox']", function () {
        if ($(".item-check").length == $(".item-check:checked").length) {
            //订单数量等于被选中订单的数量
            $('.allcheck').addClass("check").prop("checked", true);
        } else {
            $('.allcheck').removeClass("check").prop("checked", false);
        }
    });
})(jQuery);