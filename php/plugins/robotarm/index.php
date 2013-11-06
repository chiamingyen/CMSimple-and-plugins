<?php

function robotarmMain(){
    $output = <<< EOT

    <div id="container"></div>
        <script type="text/javascript">

        var _gaq = _gaq || [];
        _gaq.push(['_setAccount', 'UA-20854652-1']);
        _gaq.push(['_trackPageview']);

        (function() {
          var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
          ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
          var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
        })();

        </script>
        
    <script type="text/javascript" src="plugins/robotarm/js/robotStat.js"></script>
		<script type="text/javascript" src="plugins/robotarm/js/three/Three.js"></script>
		<script type="text/javascript" src="plugins/robotarm/js/three/Detector.js"></script>
		<script type="text/javascript" src="plugins/robotarm/js/three/RequestAnimationFrame.js"></script>

		<script type="text/javascript"> 

			if ( ! Detector.webgl ) Detector.addGetWebGLMessage();

			var container, stats;

			var camera, scene, renderer, light;

			var base, mesh2, mesh3, mesh4, hand;  // robot parts
			
			// robot dummy joints
			// if you have always wanted to program a robot, now is your chance. When this page
			// loads to control the robot arms you need to simply issue the following commands:
			//
			// dummy.rotation.y  = # <- you assign a number ( you will need to play with this to understand magnitude )
			// dummy2.rotation.z = #
			// dummy3.rotation.z = #
			// dummy4.rotation.z = #
			// 
			// have fun...
			var dummy = new THREE.Object3D();  // base to body joint (rotation limited to y axis)
			var dummy2 = new THREE.Object3D(); // body to arm1 (rotation limited to z axis)
			var dummy3 = new THREE.Object3D(); // arm1 to arm2 (rotation limited to z axis)
			var dummy4 = new THREE.Object3D(); // arm2 to hand (rotation limited to z axis)
			
			var dummyArray = [];
			
			// use this for keyboard control
			var tabValue = 0;

            // cameria variables
            var radious = 7000, theta = 45, phi = 60, onMouseDownTheta = 45, onMouseDownPhi = 60,
                isMouseDown = false, onMouseDownPosition, mouse3D, projector, ray;
            
			init();
			animate();

			function init() {

				container = document.getElementById( 'container' );
				
				camera = new THREE.Camera( 50, window.innerWidth / window.innerHeight, 1, 10000 );
				camera.position.x = radious * Math.sin( theta * Math.PI / 360 ) * Math.cos( phi * Math.PI / 360 );
				camera.position.y = radious * Math.sin( phi * Math.PI / 360 );
				camera.position.z = radious * Math.cos( theta * Math.PI / 360 ) * Math.cos( phi * Math.PI / 360 );
				camera.target.position.y = 200;
				//camera.lookAt(0,200,0);

				scene = new THREE.Scene();

				scene.addLight( new THREE.AmbientLight( 0x333333 )  );

				light = new THREE.DirectionalLight( 0xffffff );
				light.position.set( 0, 0, 1 );
				light.position.normalize();
				scene.addLight( light );
				
        //load robot model
				var loader = new THREE.JSONLoader();
				
				loader.load( { model: "plugins/robotarm/obj/robot_arm_base.js", callback: createBase } );
				loader.load( { model: "plugins/robotarm/obj/robot_arm_body.js", callback: createBody } );
				loader.load( { model: "plugins/robotarm/obj/robot_arm_arm1.js", callback: createArm1 } );
				loader.load( { model: "plugins/robotarm/obj/robot_arm_arm2.js", callback: createArm2 } );
				loader.load( { model: "plugins/robotarm/obj/robot_arm_hand.js", callback: createHand } );
				
        // renderer start
				renderer = new THREE.WebGLRenderer( { antialias: true } );
				renderer.setSize( window.innerWidth, window.innerHeight );

				container.appendChild( renderer.domElement );
				
				// add note to page
				addText = document.createElement( 'div' );
				addText.style.position = 'absolute';
				addText.style.color = '#000';
				addText.style.top = '10px';
				addText.style.left = '10px';
				addText.innerHTML = 'Use Arrow Keys <- -> , toggle axis by hitting space bar';
				container.appendChild( addText );
        
        onMouseDownPosition = new THREE.Vector2();
        
				document.addEventListener( 'mousemove', onDocumentMouseMove, false );
				document.addEventListener( 'mousewheel', onDocumentMouseWheel, false );
				document.addEventListener( 'mousedown', onDocumentMouseDown, false );
				document.addEventListener( 'mouseup', onDocumentMouseUp, false );
				
				document.addEventListener( 'keydown', onDocumentKeyDown, false );
				//document.addEventListener( 'keyup', onDocumentKeyUp, false );
			}
			//keyboard events
			function onDocumentKeyDown( event ) {

				switch( event.keyCode ) {
					
					case 32: toggleJoint(); break; // tab

					case 37: offsetScene(-1,0); break;     //arrow <-
					case 39: offsetScene( 1,0); break;     //arrow ->
					//case 38: offsetScene( 0, -1); break; //arrow /\
					//case 40: offsetScene( 0, 1 ); break; //arrow \/

				}

			}
            // 交換控制軸函式
            function toggleJoint() {
            
                if (tabValue === dummyArray.length - 1) {
                    tabValue = 0;
                }
                else {
                    tabValue++;
                }
            
            }
			function onDocumentKeyUp( event ) {

				switch( event.keyCode ) {

					//case 16: isShiftDown = false; interact(); render(); break;

				}

			}
            
            // 主要旋轉軸在 y 與 z 軸
			function offsetScene( offsetX, offsetY ) {
			    
			    var mag = 0.01;
			    // currently offsetY not used
			    if (dummyArray[tabValue].control === 'y') {
                    dummyArray[tabValue].rotation.y = dummyArray[tabValue].rotation.y + Math.sin(offsetX*mag);
                }
                if (dummyArray[tabValue].control === 'z') {
                    dummyArray[tabValue].rotation.z = dummyArray[tabValue].rotation.z + Math.sin(offsetX*mag);
                }
                robotStat.update(dummyArray);	    
			}
			// mouse events
            function onDocumentMouseWheel( event ) {

				radious -= event.wheelDeltaY;

				camera.position.x = radious * Math.sin( theta * Math.PI / 360 ) * Math.cos( phi * Math.PI / 360 );
				camera.position.y = radious * Math.sin( phi * Math.PI / 360 );
				camera.position.z = radious * Math.cos( theta * Math.PI / 360 ) * Math.cos( phi * Math.PI / 360 );

			}
			function onDocumentMouseDown( event ) {

				event.preventDefault();

				isMouseDown = true;

				onMouseDownTheta = theta;
				onMouseDownPhi = phi;
				onMouseDownPosition.x = event.clientX;
				onMouseDownPosition.y = event.clientY;

			}
			function onDocumentMouseMove( event ) {

				event.preventDefault();

				if ( isMouseDown ) {

					theta = - ( ( event.clientX - onMouseDownPosition.x ) * 0.5 ) + onMouseDownTheta;
					phi = ( ( event.clientY - onMouseDownPosition.y ) * 0.5 ) + onMouseDownPhi;

					phi = Math.min( 180, Math.max( 0, phi ) );

					camera.position.x = radious * Math.sin( theta * Math.PI / 360 ) * Math.cos( phi * Math.PI / 360 );
					camera.position.y = radious * Math.sin( phi * Math.PI / 360 );
					camera.position.z = radious * Math.cos( theta * Math.PI / 360 ) * Math.cos( phi * Math.PI / 360 );
				}
			}
			
			function onDocumentMouseUp( event ) {

				event.preventDefault();

				isMouseDown = false;

				onMouseDownPosition.x = event.clientX - onMouseDownPosition.x;
				onMouseDownPosition.y = event.clientY - onMouseDownPosition.y;

			}
			// --------- end mouse control --------------------
			// obj/robot_arm_base.js 為 base 的幾何資料, 作為 createBase 的 geometry 輸入
			function createBase( geometry ) {

				geometry.materials[0][0].shading = THREE.FlatShading;

				var material = new THREE.MeshFaceMaterial();
                // 將 robot_arm_base 加上材質設定後, 存為 base
				base = new THREE.Mesh( geometry, material );
				// 設定 base 的比例大小
				base.scale.x = base.scale.y = base.scale.z = 75;
				// 為 base 設定旋轉軸
                // dummy 為 base to body joint (rotation limited to y axis)
				// adding joint for body
				base.addChild (dummy);
				// control body location by moving joint x,y,z
				dummy.position.y = 18;
				dummy.control = 'y'; // y axis is controlled
				// add all dummy joints to an array so i can control them easier later
				dummyArray.push(dummy);
				

			}
			
			function createBody( geometry ) {

				geometry.materials[0][0].shading = THREE.FlatShading;

				var material = new THREE.MeshFaceMaterial();

				mesh2 = new THREE.Mesh( geometry, material );
				
				dummy.addChild(mesh2);
				
				// adding joint for arm1 
				dummy.addChild(dummy2);
				dummy2.position.x = 0;
				dummy2.position.y = -8;
				dummy2.control = 'z'; // z axis is controlled
				dummyArray.push(dummy2);

			}
			
			function createArm1( geometry ) {

				geometry.materials[0][0].shading = THREE.FlatShading;

				var material = new THREE.MeshFaceMaterial();

				mesh3 = new THREE.Mesh( geometry, material );
				
				dummy2.addChild(mesh3);
				
				// add joint for arm 2
				dummy2.addChild(dummy3);
				// these offsets are set manually
				dummy3.position.x = -16.5;
				dummy3.position.y = 14;
				dummy3.control = 'z';
				dummyArray.push(dummy3);
				
			}
			function createArm2( geometry ) {

				geometry.materials[0][0].shading = THREE.FlatShading;

				var material = new THREE.MeshFaceMaterial();

				mesh4 = new THREE.Mesh( geometry, material );
				
				dummy3.addChild(mesh4);
				
				dummy3.addChild(dummy4);
				// these offsets are set manually
				dummy4.position.x = -18.5;
				dummy4.position.y = 5.5;
				dummy4.control = 'z';
				dummyArray.push(dummy4);
				
			}
			function createHand( geometry ) {

				geometry.materials[0][0].shading = THREE.FlatShading;

				var material = new THREE.MeshFaceMaterial();

				hand = new THREE.Mesh( geometry, material );
				
				dummy4.addChild(hand);
				
				// this line of code must be at the very end.
				scene.addObject( base );
				
				// this must run once the file is fully loaded
				// add robot stat widget
                robotStat.init(dummyArray);	
			}
			function animate() {

				requestAnimationFrame( animate );
				render();

			}

			function render() {

				renderer.render( scene, camera );

			}
        
		</script>
EOT;
    
return $output;
            
}