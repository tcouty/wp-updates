jQuery(document).ready(function($){
    var frame;
    $('#ch_admin_upload_logo_button').on('click', function(e){
        e.preventDefault();
        if(frame){ frame.open(); return; }
        frame = wp.media({
            title: 'Select or Upload Logo',
            button: { text: 'Use this logo' },
            multiple: false
        });
        frame.on('select', function(){
            var attachment = frame.state().get('selection').first().toJSON();
            $('#ch_admin_login_logo').val(attachment.url);
            $('#ch_admin_logo_preview').html('<img src="'+attachment.url+'" style="max-height:100px;" />');
        });
        frame.open();
    });
});
