jQuery( document ).on( 'click', '#bettercontent-notifyme', function() {
    var post_id = jQuery(this).data('id');
    var post_action = jQuery(this).data('action');
    
    if (post_action == 'del') 
        jQuery(this).data('action', 'add');
    else 
        jQuery(this).data('action', 'del');
   
    jQuery.ajax({
        url : postbettercontent.ajax_url,
        type : 'post',
        data : {
            action : 'post_bettercontent_add_notifyme',
            post_id : post_id,
            post_action : post_action
        },
        success : function( response ) {
            jQuery('#bettercontent-notifyme').html( response );
        }
    });

    return false;
});

jQuery( document ).on( 'click', '#bettercontent-favorite', function() {
    var post_id = jQuery(this).data('id');
    var post_action = jQuery(this).data('action');
    
    if (post_action == 'del') 
        jQuery(this).data('action', 'add');
    else 
        jQuery(this).data('action', 'del');
    
    jQuery.ajax({
        url : postbettercontent.ajax_url,
        type : 'post',
        data : {
            action : 'post_bettercontent_add_favorite',
            post_id : post_id,
            post_action : post_action
        },
        success : function( response ) {
            jQuery('#bettercontent-favorite').html( response );
        }
    });

    return false;
});

jQuery(function() {
    jQuery( ".bettercontent-sortable" ).sortable({
        update: function(event, ui) {
            var favorite_sort = new Array();
            var sort_index = 0;
            
            message = 'New sorting event:\n';
            jQuery( this ).find('li').each(function( index ) {
                favorite = new Object();
                favorite["favorite_id"] = jQuery( this ).data('favorite_id');
                favorite["sort"] = sort_index;
                favorite_sort.push(favorite);
                sort_index++;
            });
               
            jsonFavorites = JSON.stringify(favorite_sort);
                
            // ++++++++++++++++++++++++++++++++++++++++++++++++
            // Now you could make an ajax call to some php file
            // or whatever, storing the new order in a database
            // ++++++++++++++++++++++++++++++++++++++++++++++++
            jQuery.ajax({
                url : postbettercontent.ajax_url,
                type : 'post',
                data : {
                    action : 'post_bettercontent_favorite_sort',
                    'favorite_sort': jsonFavorites
                },
                success : function( response ) {
                    // Nothing to do here ... for the time being.
                }
            });
        }
    });
    
    jQuery( ".bettercontent-sortable" ).disableSelection();
});
