var $j = jQuery.noConflict();

$j(document).ready(function(){ 


$j(".likeThis").click(function() {
	var classes = $j(this).attr("class");
	classes = classes.split(" ");
	
	if(classes[1] == "done") {
		return false;
	}
	var classes = $j(this).addClass("done");

	var id = $j(this).attr( "data-post_id" );
	var nonce = $j(this).attr("data-nonce")

	$j.ajax({
	  type: "POST",
	  dataType: 'json',
	  url: themeists_ajax.ajaxurl,
	  data: {action: "themeists_like_this_vote", post_id : id, nonce: nonce},
	  success: function(response){
	  	if(response.type == "success") {
        	$j("#like-" + id).html(response.vote_count)
        }
	  }
	}); 
	
	
	return false;
});

});