"undefined" == typeof PlgProCritical && (PlgProCritical = {}) ;

PlgProCritical.Options = Joomla.getOptions('PlgProCritical') ;

PlgProCritical.Init = function(){
    var viewArr = ['css_file'] ;
    var view = PlgProCritical.Options.view

    if ( viewArr.indexOf( view ) === -1 ) return ;
    var init =  PlgProCritical[view+'_Init'] ;
    init();
};

PlgProCritical.css_file_Init = function(){
    var $ = jQuery ;
    var $Input_s= $('[name*="[file]"],[name*="[minify_file]"],[name*="[override_file]"]');
    var ppc = new PPC();
    ppc.addBtnOpenFile( $Input_s );
    ppc.initRadio_Files();
};

var PPC = function(){
    var $ = jQuery ;

    /**
     * Стандартные параметры Ajax запроса
     * @type {{plugin: string, option: string, group: string}}
     */
    this.defAjaxParam = {
        option: 'com_ajax',
        group: 'system',
        plugin: 'pro_critical',
    };

    /**
     * Добавить кнопку открыть файл в новом окне к полю ссылка на файл
     * Проверяет что бы окончание значения в поле было .js||.css
     * @param $inpFileArr - jQuery объект <input type="text" />
     */
    this.addBtnOpenFile = function ($inpFileArr) {
        $inpFileArr.each( function (i,el) {
            var v = $(el).val() ;
            if ( !v || ( !v.match("\.js$") && !v.match("\.css$")  ) ) return ;
            if (v.match("^http")) {
                console.log( v )

            }

            $(el).parent().append( $('<div />',{
                class :'btn-group viewsite',
                html: $('<a />',{
                    href : v ,
                    html : '<span class="icon-out-2" aria-hidden="true"></span><i>Open</i></a>',
                    target : '_blank'
                }),
            }));
        });
    };

    this.RadioEvtElement = [
        'jform_minify',
        'jform_ver_type',
        'jform_file_debug',
        'jform_cash_external',
        'jform_cash_time',
    ];
    /**
     * повесить обработчик кино радиокнопки
     */
    this.initRadio_Files = function () {
        $this = this ;
        $.each(this.RadioEvtElement , function (i,a) {
            var $el = $('#'+a+' input , select#'+a );
            $el.on('change',{elem:$el},$this[a]);
        });
    };
    /**
     * Получить состояние радио кнопки
     * @param e - event
     * @returns {boolean} - если кнопка влючена true
     */
    this.chRadio = function (e) {
        return !!(+e.data.elem.parent().find('input:checked').val());
    };
    /**
     * switch radio button
     * @param id - str  fieldset id etc('#jform_override')
     * @param status - str 'on' or 'off'||empty
     */
    this.setRadio = function (id , status) {
        var $elem = $(id);
        var input = $elem.find('[value="'+(status==='on'?1:0)+'"]');
        var label = input.next();
        if (!input.prop('checked')) {
            label.closest('.btn-group').find('label').removeClass('active btn-success btn-danger btn-primary');
            if (input.val() === '') {
                label.addClass('active btn-primary');
            } else if (+input.val() === 0) {
                label.addClass('active btn-danger');
            } else {
                label.addClass('active btn-success');
            }
            input.prop('checked', true);
            input.trigger('change');
        }
    };




    /**
     * события кэшировать внешний ресурс
     * @param event
     */
    this.jform_cash_external = function (event) {
        var ppc = new PPC();
        var option  = $('form#adminForm').serialize();
        var objData = {
            model: '\\Helpers\\Assets\\External',
            task: 'saveExternal' ,
            data: option,
        };
        $.extend(objData, ppc.defAjaxParam);
        ppc.send(objData).then(function (result) {
                var $override_file = $('#jform_override_file')
                $override_file.val(result.data.override_file);
                $('#jform_last_update').val(result.data.last_update);
                ppc.setRadio('#jform_override', (result.data.override===1?'on':''));
                ppc.addBtnOpenFile( $override_file )
            },function (reject) {
                console.log(reject) ;
                alert('Ошибки !!! this.jform_ver_type')
            });



    };





    this.jform_cash_time = function (event) {
        var ppc = new PPC();
        var v = +$(this).val();
        if (  v > 0  ){
            var verField = $('#jform_revision_id') ;
            verField.val(+ new Date());
            ppc.setRadio('#jform_ver_type', ('on'));
        }
        ppc.saveForm();
    };
    /**
     * Отладка файла
     * @param event
     */
    this.jform_file_debug = function (event) {
            var ppc = new PPC();
            ppc.saveForm();
    };
    /**
     * Radio - Media ver.
     * @param event
     */
    this.jform_ver_type = function (event) {
        var ppc = new PPC();
        var verField = $('#jform_revision_id') ;
        var v =  ppc.chRadio(event);
        if (!v){
            verField.val('');
        }else{
            verField.val(+ new Date());
        }
        ppc.saveForm()
    };



    this.saveForm = function () {
        var ppc = new PPC();
        var option  = $('form#adminForm').serialize();
        var objData = {
            model: '\\Models\\Route',
            task: 'save',
            data: option,
        };
        $.extend(objData, ppc.defAjaxParam);
        this.send(objData);
    };

    /**
     * Отправка простых запросов к моделям
     * Сохранить - Обновить - пр.
     * @param objData
     */
    this.send = function (objData ) {
        return new Promise(function (resolve, reject) {
            var gnz11 = new GNZ11();
            gnz11.getAjax().then(function (Ajax) {
                Ajax.Setting.Ajax.auto_render_message = true;
                Ajax.Setting.Noty.timeout = 2000;
                Ajax.send(objData, 'admin_script_send').then(function (result) {
                    resolve(result) ;

                },function (err) {
                    reject(err)
                });
            });
        })

    };






    // Адаптер для сжимания файлов
    this.jform_minify = function (event) { var ppc = new PPC(); ppc.eventMinify(event);};

    /**
     * Обработчк События минифи файл
     *
     * Способ вызова
     * var $el = $('#jform_gnzlib_debug input');                    // - радио кнопка
     * var $file = $('[name*="[gnzlib_path_file_corejs]"]') ;       // - INP с оригинальным файлом
     * var $min = $('[name*="[gnzlib_path_file_corejs_min]"]') ;   // - INP с оригинальным файлом
     * $el.on('change',{elem:$el,file:$file ,min:$min },ppc.eventMinify);
     *
     *
     * @param event
     */
    this.eventMinify = function( event ) {

        var gnz11 = new GNZ11();
        var ppc = new PPC();


        var option  = $('form#adminForm').serialize();
        var _task = 'minify' ;

        var v = +event.data.elem.parent().find('input:checked').val() ;
        if (!v) {
            _task = 'remove_minify' ;
        }

        // Если работа только с 1 файлом
        if ( typeof event.data.file !=='undefined'){
            option = $(event.data.file[0]).val();
            // Если запрещено удаление сжатой версии
            if (!v && event.data.no_del ) {
                var mes = 'Cжатый файл не удален по условию.';
                if ( typeof event.data.no_del_mes !=='undefined' )
                    mes = event.data.no_del_mes ;
                gnz11.getAjax().then(function (Ajax) {
                    Ajax.renderNoty(mes)
                });
                return ;
            }
        }
        var data = {
            file: $('[name*="[file]"]'),
            min: $('[name*="[minify_file]"]'),
        };
        var objData = {
            model: '\\Optimize\\Js_css',
            task: _task,
            data: option,
        };
        $.extend(objData, ppc.defAjaxParam);


        gnz11.getAjax().then(function (Ajax) {

            Ajax.Setting.Ajax.auto_render_message = true ;
            Ajax.Setting.Noty.timeout = 3000 ;
            Ajax.send(objData , 'admin_script').then(function (result) {

                // Если задача сжимать и в ответе есть информация о файлах
                if (objData.task === 'minify' && typeof result.data.files !== 'undefined' ){
                    if ( typeof result.data.files.minify_file === 'undefined'){
                        Ajax.renderNoty('Путь к сжатому файлу === undefined'  , 'error');
                        return ;
                    }
                    $(data.min).val(result.data.files.minify_file);
                    ppc.addBtnOpenFile( $(data.min) )
                }
            });
        },function (err) {
            console.error(err)
        });

    }
};






document.addEventListener("DOMContentLoaded", function () {
    PlgProCritical.Init();
});


