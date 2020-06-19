(function($) {
     //点击左侧导航功能
     $(".sub-nav").on("click","li",function(){
        $(this).parents(".list-item").addClass("active").siblings(".list-item").removeClass("active");
        $(this).addClass("active").siblings("li").removeClass("active");
        $(".list-item").each(function(){
            var _this=$(this);
            if(!_this.hasClass("active")){
                _this.find("li").removeClass("active");
            }
        });
    });
    //一级导航折叠
    $(".list-item").on("click",".nav-tit",function(){
        $(this).siblings(".sub-nav").toggle()
    });

    $(".sub-nav li").each(function(){
        var _this=$(this);
        var arr;
        if(_this.attr('data-src')){
            arr=_this.attr("data-src");
        }else{
            arr=" ";
        }
       var arr1=arr.split(",");
        if($(this).find("a").attr("href")==window.location.href || $(this).find("a").attr("href")==window.location.pathname || arr1.indexOf(window.location.pathname)!= -1){
             _this.addClass("active");
             _this.parents(".list-item").addClass("active")
        }
    });
    //执行news-list划过显示二级导航
    $('.news-list').on('mouseenter', function() {
        $('.personal-list').addClass('personal-list2');
        $(this).css('box-shadow', '0 3px 4px 0 rgba(0,0,0,0.10)')
    })

    //执行news-list划过关闭二级导航
    $('.news-list').on('mouseleave', function() {
        $('.personal-list').removeClass('personal-list2');
        $(this).css('box-shadow', 'none')
    })

    //执行personal-list划过二级变色功能
    $('.personal-list').on('mouseenter', 'li', function() {
        $(this).css('color', '#FF8544');
    })

    //执行personal-list离开二级取消变色功能
    $('.personal-list').on('mouseleave', 'li', function() {
        $(this).css('color', '#9699A4');
    })
})(jQuery);