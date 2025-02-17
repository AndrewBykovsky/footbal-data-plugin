(function ($) {
    'use strict';

    initLoadLeagueList();

    function initLoadLeagueList() {
        const plugindata = $('.fdp-data-form');

        if (plugindata.length > 0) {
            const leagueListHolder = plugindata.find('.league-list--js');
            const loader = plugindata.find('.fdp-loader--js');

            let data = {
                'action': 'fdp_get_league_list',
                'security': params.ajax_nonce
            };

            $.ajax({
                url: params.ajaxurl,
                data: data,
                type: 'POST',
                beforeSend: function () {
                },
                success: function (data) {
                    if (data.success === true) {
                        leagueListHolder.append(data.list_html);
                        initFilterLeague();
                    } else {
                        plugindata.append('<div class="error-message"><p>' + data.msg + '</p></div>');
                    }
                },
                complete: function () {
                    loader.css('display', 'none');
                }
            });
        }
    }

    function initFilterLeague() {
        const plugindata = $('.fdp-data-form');
        const selectLeague = plugindata.find('.fdp-data-form__top select');
        const datesFields = plugindata.find('.fdp-data-form__top .dates .date-field--js');
        const clearDates = plugindata.find('.fdp-data-form__top .dates .dates-clear--js');

        selectLeague.on('change', function () {
            let data = {
                'action': 'fdp_get_league_data',
                'security': params.ajax_nonce,
                'code': selectLeague.val()
            };

            filterLeague(data);
        })

        datesFields.each(function () {
            $(this).on('change', function () {
                let data = {
                    'action': 'fdp_get_league_data',
                    'security': params.ajax_nonce,
                    'code': selectLeague.val(),
                    'dateFrom': $('.fdp-data-form .fdp-data-form__top .dates #date-from').val(),
                    'dateTo': $('.fdp-data-form .fdp-data-form__top .dates #date-to').val()
                };

                filterLeague(data);
            })
        })

        clearDates.on('click', function () {
            $('.fdp-data-form .fdp-data-form__top .dates #date-from').val('');
            $('.fdp-data-form .fdp-data-form__top .dates #date-to').val('');

            let data = {
                'action': 'fdp_get_league_data',
                'security': params.ajax_nonce,
                'code': selectLeague.val()
            };

            filterLeague(data);
        })
    }

    function filterLeague(data) {
        const plugindata = $('.fdp-data-form');
        const loader = plugindata.find('.fdp-loader--js');
        const tableHolder = plugindata.find('.fdp-data-form__bottom--js');

        $.ajax({
            url: params.ajaxurl,
            data: data,
            type: 'POST',
            beforeSend: function () {
                loader.css('display', 'flex');
                $('.fdp-data-table--js').remove();
            },
            success: function (data) {
                if (data.success === true) {
                    tableHolder.append(data.data_html);
                }
            },
            complete: function () {
                loader.css('display', 'none');
            }
        });
    }

})(jQuery);