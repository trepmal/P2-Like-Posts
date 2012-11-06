jQuery( document ).ready( function($) {

	$('#postlist').on('click', '.toggle-like', function(ev) {
		ev.preventDefault();
		var btn = $(this),
			thispost = btn.parents('.post'),
			id = thispost.attr('id').replace('prologue-', '');
		// console.log( id );
		btn.text('...');
		toggle_like( thispost, id );
	});

	function toggle_like( post, id ) {
		$.post( like.ajaxurl, {
				'action' : 'toggle_like',
				'post_id' : id
		}, function( response ) {
			console.log( response );

			update_like( post, response.like );
			update_count( post, response.total );
			update_who_like( post, response.who );

		}, 'json' );
	}

	function update_like( post, rating ) {
		post.find('.toggle-like').html('&hearts;');
		if ( rating == 'Like' ) {
			post.find('.toggle-like').css('color','');
		} else {
			post.find('.toggle-like').css('color','#c55');
		}
	}
	function update_count( post, rating ) {
		post.find('.count-like').text(rating);
	}

	function update_who_like( post, rating ) {
		post.find('.like-posts').attr('title',rating);
	}

});