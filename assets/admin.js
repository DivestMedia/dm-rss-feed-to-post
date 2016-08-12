jQuery(function($){
    $('#feed-check').click(function(){
        var thisbtn =       $(this);
        thisbtn.val('Checking Feed...');
        thisbtn.addClass('disabled');
        // Call Ajax

        $.post('/wp-admin/admin-ajax.php',{
            action : 'rss_feed_check',
            id : $('#post_ID').val()
        },function(data){
            if(data){
                data = JSON.parse(data);
                if(typeof data.data != 'undefined'){
                    for(var i in data.data){
                        var item = data.data[i];
                        console.log(item);
                        for(var ii in item){
                            var d = item[ii];
                            $('#feed-details-area tbody').append('<tr id="'+d.key+'"><th>'+d.label+'</th><td>'+d.value+'</td></tr>');
                        }
                    }
                }

                if(typeof data.log != 'undefined'){
                    $('#log-area').removeClass('hidden');
                    $('#log-area').empty();
                    for(var i in data.log){
                        var thislog = data.log[i];
                        $('#log-area').append('['+thislog.time+'] '+thislog.status+' | '+thislog.type+' : '+thislog.message+'\n');
                    }
                }
            }

                thisbtn.val('Check Feed');
                thisbtn.removeClass('disabled');
        });
    });
});
