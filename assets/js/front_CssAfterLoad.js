/**
 * Отложенная загрузка css стилей
 */
(function () {
    setTimeout(function () {
        var elements = document.querySelectorAll('#CssAfterLoad'),
            option ,   body ;

        elements  = JSON.parse(elements[0].textContent);
        console.log( elements )
        for (var i = 0, l = elements.links.length; i < l; i++) {
            addTag(elements.links[i]);
        }
        body= "style="+ JSON.stringify( elements.style )  ;
        getStyle(body);


    },2000);

    function getStyle(body) {
        var i = '?'+Joomla.getOptions('csrf.token')+'=1';
        i+='&format=json';
        i+='&option=com_ajax';
        i+='&group=system';
        i+='&plugin=pro_critical';
        i+='&model=\\Helpers\\Assets\\Css\\Style';
        i+='&task=getAjaxStyleData';

        var xhr = new XMLHttpRequest();
        xhr.open('POST', '/index.php'+i , true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.send( body );
        xhr.onreadystatechange = function() {
            if (xhr.readyState !== 4) return;
            if (xhr.status !== 200) {
               console.warn( xhr.status + ': ' + xhr.statusText )
            } else {
                var element  = JSON.parse(xhr.responseText);
                var styleTag = document.createElement("style");
                styleTag.innerHTML = element.data.style;
                document.getElementsByTagName('HEAD')[0].appendChild(styleTag);
            }
        }
    }

    function addTag(href) {
        var link = document.createElement('link');
        link.rel = 'stylesheet';
        link.type = 'text/css';
        link.href = href;
        document.getElementsByTagName('HEAD')[0].appendChild(link);
    }

})();