/**
 * this file belongs to the recentcomments wordpress plugin
 * get it at <http://wordpress.org/extend/plugins/recentcomments>
 */
(function(){
function addHover(id) {
  var wrapper = document.getElementById(id);
  if(wrapper) {
    var items = wrapper.getElementsByTagName('li');
    if(items && items.length > 0) {
      for(var k=0; k<items.length; k++) {
        items[k].onmouseover = function() {
          this.className = 'hover';
          return false;
        }
        items[k].onmouseout = function() {
          this.className = '';
          return false;
        }
      }
    }
  }
}
addHover('recentmisc');
addHover('recentcomment');
addHover('recenttrackback');
addHover('recentpingback');

})();