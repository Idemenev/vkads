$(function () {

    $('input[type="button"]').click(function(ev) {
        var adId = $(this).attr('name').replace('id-', '');
        var table = $(this).parents('table')[0];

        var textarea = $(table).find('textarea');
        var action = $(this).hasClass('destroy') ? 'destroy' : 'update';

        var cabinetId = document.location.href.match(/cabinet\/([^\/]+)\//)[1]

        $('#preloader').show();
        $.post('/vk/ads/' + action,
            {'comment': $(textarea).val(), 'adId': adId, 'cabinetId': cabinetId, '_token': $('meta[name="csrf-token"]').attr('content')},
            function (data) {
                $('#preloader').hide();
                if (action == 'update' && data) {
                    $(textarea).val(data.comment);
                } else if (data) {
                    $(table).next('hr').remove();
                    $(table).remove();
                }
            },
            'json'
        )
    })
})
