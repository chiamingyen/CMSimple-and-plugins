robotStat = (function() {

var container = document.getElementById( 'container' );
var lastUpdate = 0;

function init () {

  // draw stat box
  var addBox = document.createElement( 'div' );
  addBox.style.position = 'absolute';
	addBox.style.color = '#3f3f3f';
	addBox.style.top = '300px';
	addBox.style.left = '300px';
	addBox.id = 'robotStat';
	container.appendChild(addBox);
	
	// first add statName
	for (var i = 0; i < dummyArray.length; i++) {
	
        //Rotation Axis
        var addLine = document.createElement('div');
        addLine.className = 'statName';
        addLine.innerHTML = 'Roation Axis '+ i +':';
        addBox.appendChild(addLine);
        // values --
        var addLine =  document.createElement('div');
        addLine.id = 'axis'+i+'ValueX';
        addLine.className = 'statValue';
        addLine.innerHTML = 'x: ' + dummyArray[i].rotation.x;
        addBox.appendChild(addLine);
        
        var addLine =  document.createElement('div');
        addLine.id = 'axis'+i+'ValueY';
        addLine.className = 'statValue';
        addLine.innerHTML = 'y: ' + dummyArray[i].rotation.y;
        addBox.appendChild(addLine);
        
        var addLine =  document.createElement('div');
        addLine.id = 'axis'+i+'ValueZ';
        addLine.className = 'statValue';
        addLine.innerHTML = 'z: ' + dummyArray[i].rotation.z;
        addBox.appendChild(addLine);
        
    }
}

function update () {

    if(lastUpdate < 5){
        lastUpdate++;
        return;
    }
    else {
        
        lastUpdate = 0;
        
        for (var i = 0; i < dummyArray.length; i++) {
            // values --
            var addLine =  document.getElementById('axis'+i+'ValueX');
            addLine.innerHTML = 'x: ' + dummyArray[i].rotation.x;
            
            var addLine =  document.getElementById('axis'+i+'ValueY');
            addLine.innerHTML = 'y: ' + dummyArray[i].rotation.y;
            
            var addLine =  document.getElementById('axis'+i+'ValueZ');
            addLine.innerHTML = 'z: ' + dummyArray[i].rotation.z;
        }
    }
}

return { update : update,
         init : init }
})();