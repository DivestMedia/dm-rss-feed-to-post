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
                    $('#feed-details-area tbody').empty();
                    for(var i in data.data){
                        var item = data.data[i];
                        for(var ii in item){
                            var d = item[ii];
                            if(typeof d.key != 'undefined'){
                                if(d.key.search('tags-')!=-1){
                                    $('#feed-details-area tbody').append('<tr id="'+d.key+'"><th style="width:300px;">'+d.label+'</th><td>'+d.value.found+'</td></tr>');
                                }else{
                                    $('#feed-details-area tbody').append('<tr id="'+d.key+'"><th style="width:300px;">'+d.label+'</th><td>'+d.value+'</td></tr>');
                                }
                            }
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

    $('#add-meta-button').click(function(e){
        e.preventDefault();
        $('.row-custom-meta').find('[name^="_rss_post_meta"]:last-of-type').each(function(){
            var $newelem = $(this).clone();
            $newelem.val('');
            $newelem.insertAfter($(this));
            $('<br>').insertBefore($newelem);
        });
    });

    $('#add-tag-button').click(function(e){
        e.preventDefault();
        $('.row-post-tags').find('[name^="_rss_post_tags"]:last-of-type').each(function(){
            var $newelem = $(this).clone();
            $newelem.val('');
            $newelem.insertAfter($(this));
            $('<br>').insertBefore($newelem);
        });
    });

    // $('form#post').on('submit',function(event){
    //     var index = 0;
    //     $('.row-post-tags').find('[name^="_rss_post_tags[meta]"]').each(function(){
    //         if($(this).val().trim().length==0){
    //                 $('.row-post-tags').find('name^="_rss_post_tags[meta]"]').eq(index).remove();
    //                 $('.row-post-tags').find('name^="_rss_post_tags[type]"]').eq(index).remove();
    //                 $('.row-post-tags').find('name^="_rss_post_tags[query]"]').eq(index).remove();
    //                 $('.row-post-tags').find('name^="_rss_post_tags[selector]"]').eq(index).remove();
    //         }
    //         index++;
    //     });
    //
    //     event.preventDefault();
    //     return false;
    // });

});
