(function () {



    var CriticalStart = function () {
        var $ = jQuery ;
        var gnz11 = new GNZ11();
        var AjaxParam = {
            option: 'com_ajax',
            group: 'system',
            plugin: 'pro_critical',
            model: '\\Helpers\\Assets\\CriticalCss\\Api',
            task: 'onAjaxApiCritical' ,

        };

        // AjaxParam.data =  JSON.parse( $('script#CriticalCss').text() )  ;
        AjaxParam.data =   $('script#CriticalCss').text()   ;
        gnz11.getAjax().then(function (Ajax) {
            Ajax.send(AjaxParam).then(function (res) {
                console.clear();
                console.log( res )
            },function (err) {
                console.log(err) ;
            })
        });

    };


    setTimeout(function () {
        console.clear();
        CriticalStart()
    } , 2000);

})();

