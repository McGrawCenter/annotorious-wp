jQuery(document).ready(function(){


     var imgid = "annotorius"; //jQuery('.annotorius').attr('id');
     var jsonurl = annotoriusvars.ajax_url+"?action=anno_get&post_id="+annotoriusvars.post_id;
    console.log(annotoriusvars);
    
    
   
    (function() {
    
      var config = {
        image: imgid // image element or ID
      }
      
      if(!annotoriusvars.loggedin || annotoriusvars.loggedin == '') { config.readOnly = true; }
    
      var anno = Annotorious.init(config);
      

      anno.loadAnnotations(jsonurl);

      // Add event handlers using .on  
      anno.on('createAnnotation', function(annotation) {
	     var d = { 'action': 'anno_add', 'post_id': annotoriusvars.post_id, 'annotation':annotation }
	     jQuery.get(annotoriusvars.ajax_url, d, function(data){
	       console.log(data);
	     });
      });
      
      anno.on('deleteAnnotation', function(annotation) {
	     var d = { 'action': 'anno_delete', 'post_id': annotoriusvars.post_id, 'annotationid':annotation.id }
	     
	     jQuery.get(annotoriusvars.ajax_url, d, function(data){
	       console.log(data);
	     });
      });
      
      anno.on('updateAnnotation', function(annotation) {
      console.log(annotoriusvars.ajax_url+"?action=update&post_id="+annotoriusvars.post_id+"&annotation="+annotation);
	     var d = { 'action': 'anno_update', 'post_id': annotoriusvars.post_id, 'annotationid':annotation.id, 'annotation':annotation }
	     
	     jQuery.get(annotoriusvars.ajax_url, d, function(data){
	       console.log(data);
	     });
      });      
      
    })()

});
