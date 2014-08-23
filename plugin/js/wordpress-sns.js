jQuery(document).ready(function($) {
  $('form.wpsns-form').submit(function(e){e.preventDefault();e.unbind();});
  $('form.wpsns-form button').click(function() {
    var postData = $('form.wpsns-form').serializeArray();
    var timeHash = $.parseJSON($('meta[name=wordpress-sns]').attr("content"));
    postData.push({"name":"time","value":timeHash.time});
    postData.push({"name":"hash","value":timeHash.hash});
    $.post("/wp-admin/admin-ajax.php?action=wordpress-sns", postData, function(data) {
      console.log(data);
    })
  });
});
