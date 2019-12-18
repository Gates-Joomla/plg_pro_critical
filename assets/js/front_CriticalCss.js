(function () {

    var script = document.createElement('script');
    script.src = '/plugins/system/pro_critical/assets/js/front_CssAfterLoad.js';
    document.head.appendChild(script);


    /*

    var script = document.createElement('script');
    script.onload = function () {
        //do stuff with the script

    };
    script.src = something;
    document.head.appendChild(script);

    */

    // Create new link Element
    /*
    var link = document.createElement('link');
    link.rel = 'stylesheet';
    link.type = 'text/css';
    link.href = 'style.css';
    document.getElementsByTagName('HEAD')[0].appendChild(link);*/

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

                console.log( res )
            },function (err) {
                console.log(err) ;
            })
        });

    };


    setTimeout(function () {

        CriticalStart()
    } , 2000);

})();

