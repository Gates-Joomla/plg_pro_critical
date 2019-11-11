"undefined" == typeof plgProCritical && (plgProCritical = {});

var plgProCritical = {
    admin_com_config: function () {
        var $ = jQuery;
        this.Options = Joomla.getOptions('PlgProCritical');
        this.Options.GNZ11 = Joomla.getOptions('GNZ11');

        this.init = function () {
            // this.Options = Joomla.getOptions('PlgProCritical')
            var $this = this;
            if (typeof PPC !== 'function') {
                var I = setInterval(function () {
                    if (typeof PPC === 'function') {
                        clearInterval(I);
                        $this.Start();
                    }
                }, 500);
            } else {
                $this.Start();
            }

        };
        this.Start = function () {
            var ppc = new PPC();
            var $Input_s = $('[name*="[gnzlib_path_file_corejs]"],[name*="[gnzlib_path_file_corejs_min]"],[name*="[gnzlib_path_modules]"]');

            // Установить значения по умолчанию
            this.setDefault($Input_s);
            // Добавить кнопку открыть файл в новом окне к полю ссылка на файл
            ppc.addBtnOpenFile($Input_s);

            var $el = $('#jform_gnzlib_debug_off input');
            $el.on('change', {
                elem: $el,
                file: $('[name*="[gnzlib_path_file_corejs]"]'),
                min: $('[name*="[gnzlib_path_file_corejs_min]"]'),
                no_del: 1,
                no_del_mes: 'Сжатый файл не удален - так как он может использоваться другим компонентом. После выключения режима отладки он будет перезаписан.'
            }, ppc.eventMinify);

        };

        /**
         * Установить значения по умолчанию Если они не установлены
         * @param $Input
         */
        this.setDefault = function ($Input) {
            var $this = this;
            var gnz11 = new GNZ11();
            $Input.each(function (i, e) {
                var name = gnz11.getBetween($(e).attr('name'),'[',']');
                if (!$(e).val()) {
                    var defaultValue = $this.Options.GNZ11[name] ;
                    $(e).val( defaultValue )
                }
            });
        }

    },
};
(function () {

    var adminConfig = new plgProCritical.admin_com_config();
    adminConfig.init();
})();


