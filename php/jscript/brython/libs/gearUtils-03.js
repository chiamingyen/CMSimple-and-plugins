/*==========================================================================*
  Filename: gearUtils-03.js
  Rev: 3
  By: Dr A.R.Collins

  Description: JavaScript involute gear drawing utilities.
  Latest version:  <www.arc.id.au/gearDrawing.html>
  
  Kindly give credit to ARC and reference <http://www.arc.id.au/>

  Requires:
  'involuteBezCoeffs' can stand alone,
  'createGearTooth' and 'createIntGearTooth' generate draw commands for use
  in Cango graphics library but may be simply converted for use in SVG.

  Date   |Description                                                   |By
  --------------------------------------------------------------------------
  20Feb13 First public release                                           ARC
  21Feb13 Clarified variable names of start and end parameters           ARC
  06Mar13 Fixed Rf and filletAngle calculations                          ARC
 *==========================================================================*/

/* ----------------------------------------------------------
 * involuteBezCoeffs
 *
 * JavaScript calculation of Bezier coefficients for
 * Higuchi et al. approximation to an involute.
 * ref: YNU Digital Eng Lab Memorandum 05-1
 *
 * Parameters:
 * module - sets the size of teeth (see gear design texts)
 * numTeeth - number of teeth on the gear
 * pressure angle - angle in degrees, usually 14.5 or 20
 * order - the order of the Bezier curve to be fitted [3, 4, 5, ..]
 * fstart - fraction of distance along tooth profile to start
 * fstop - fraction of distance along profile to stop
 *-----------------------------------------------------------*/
function involuteBezCoeffs(module, numTeeth, pressureAngle, order, fstart, fstop)
{
  var pi = Math.PI;
  var Rpitch = module*numTeeth/2;       // pitch circle radius
  var phi = pressureAngle || 20;        // pressure angle
  var Rb = Rpitch*Math.cos(phi*pi/180); // base circle radius
  var Ra = Rpitch+module;               // addendum radius (outer radius)
  var ta = Math.sqrt(Ra*Ra-Rb*Rb)/Rb;   // involute angle at addendum
  var stop = fstop || 1;
  var start = 0.01;
  if ((typeof fstart != 'undefined')&&(fstart<stop))
    start = fstart;
  var te = Math.sqrt(stop)*ta;          // involute angle, theta, at end of approx
  var ts = Math.sqrt(start)*ta;         // involute angle, theta, at start of approx
  var p = order || 3;                   // order of Bezier approximation

  function chebyExpnCoeffs(j, func)
  {
    var N = 50;      // a suitably large number  N>>p
    var c = 0;
    for (var k=1; k<=N; k++)
    {
      c += func(Math.cos(pi*(k-0.5)/N)) * Math.cos(pi*j*(k-0.5)/N);
    }
    return 2*c/N;
  }

  function chebyPolyCoeffs(p, func)
  {
    var coeffs = [];
    var fnCoeff = [];
    var T = [[], []];
    // populate 1st 2 rows of T
    for (var i=0; i<p+1; i++)
    {
      T[0][i] = 0;
      T[1][i] = 0;
    }
    T[0][0] = 1;
    T[1][1] = 1;
    /* now generate the Chebyshev polynomial coefficient using
       formula T(k+1) = 2xT(k) - T(k-1) which yields
    T = [ [ 1,  0,  0,  0,  0,  0],    // T0(x) =  +1
          [ 0,  1,  0,  0,  0,  0],    // T1(x) =   0  +x
          [-1,  0,  2,  0,  0,  0],    // T2(x) =  -1  0  +2xx
          [ 0, -3,  0,  4,  0,  0],    // T3(x) =   0 -3x    0   +4xxx
          [ 1,  0, -8,  0,  8,  0],    // T4(x) =  +1  0  -8xx       0  +8xxxx
          [ 0,  5,  0,-20,  0, 16],    // T5(x) =   0  5x    0  -20xxx       0  +16xxxxx
          ...                     ];
    */
    for (var k=1; k<p+1; k++)
    {
      T[k+1] = [0];
      for (var j=0; j<T[k].length-1; j++)
      {
        T[k+1][j+1] = 2*T[k][j];
      }
      for (var j=0; j<T[k-1].length; j++)
      {
        T[k+1][j] -= T[k-1][j];
      }
    }
    // convert the chebyshev function series into a simple polynomial
    // and collect like terms, out T polynomial coefficients
    for (var k=0; k<=p; k++)
    {
      fnCoeff[k] = chebyExpnCoeffs(k, func);
      coeffs[k] = 0;
    }
    for (var k=0; k<=p; k++)
    {
      for (var pwr=0; pwr<=p; pwr++)    // loop thru powers of x
      {
        coeffs[pwr] += fnCoeff[k]*T[k][pwr];
      }
    }
    coeffs[0] -= chebyExpnCoeffs(0, func)/2;  // fix the 0th coeff

    return coeffs;
  }

  // Equation of involute using the Bezier parameter t as variable
  function involuteXbez(t)
  {
    // map t (0 <= t <= 1) onto x (where -1 <= x <= 1)
    var x = t*2-1;
    //map theta (where ts <= theta <= te) from x (-1 <=x <= 1)
    var theta = x*(te-ts)/2 + (ts + te)/2;
    return Rb*(Math.cos(theta)+theta*Math.sin(theta));
  }

  function involuteYbez(t)
  {
    // map t (0 <= t <= 1) onto x (where -1 <= x <= 1)
    var x = t*2-1;
    //map theta (where ts <= theta <= te) from x (-1 <=x <= 1)
    var theta = x*(te-ts)/2 + (ts + te)/2;
    return Rb*(Math.sin(theta)-theta*Math.cos(theta));
  }

  function binom(n, k)
  {
    var coeff = 1;
    for (var i = n-k+1; i <= n; i++)
    {
      coeff *= i;
    }
    for (var i = 1; i <= k; i++)
    {
      coeff /= i;
    }
    return coeff;
  }

  function bezCoeff(i, func)
  {
    // generate the polynomial coeffs in one go
    var polyCoeffs = chebyPolyCoeffs(p, func)

    for (var bc=0, j=0; j<=i; j++)
    {
      bc += binom(i,j)*polyCoeffs[j]/binom(p,j)
    }
    return bc;
  }

  // calc Bezier coeffs
  var bzCoeffs = [];
  for (var bcoeff, i=0; i<=p; i++)
  {
    bcoeff = {};
    bcoeff.x = bezCoeff(i, involuteXbez);
    bcoeff.y = bezCoeff(i, involuteYbez);
    bzCoeffs.push(bcoeff);
  }

  return bzCoeffs;
}

/*----------------------------------------------------------
  createGearTooth
  Create an array of drawing commands and their coordinates
  to draw a single spur gear tooth based on a circle
  involute using the metric gear standards.

  Requires Cango graphics library Rev 2.08 or later
 ----------------------------------------------------------*/
function createGearTooth(module, teeth, pressureAngle)
{
  function genInvolutePolar(Rb, R)  // Rb = base circle radius
  {
    // returns the involute angle as function of radius R.
    return (Math.sqrt(R*R - Rb*Rb)/Rb) - Math.acos(Rb/R);
  }

  function rotate(pt, rads)  // rotate pt by rads radians about origin
  {
    var sinA = Math.sin(rads);
    var cosA = Math.cos(rads);
    return {x: pt.x*cosA - pt.y*sinA,
            y: pt.x*sinA + pt.y*cosA};
  }

  function toCartesian(radius, angle)   // convert polar coords to cartesian
  {
    return {x: radius*Math.cos(angle),
            y: radius*Math.sin(angle)};
  }
  // ****** external gear specifications
  var m = module;                // Module = mm of pitch diameter per tooth
  var Z = teeth;                 // Number of teeth
  var phi = pressureAngle || 20; // pressure angle (degrees)
  var addendum = m;              // distance from pitch circle to tip circle
  var dedendum = 1.25*m;         // pitch circle to root, sets clearance
  var clearance = dedendum - addendum;
  // Calculate radii
  var Rpitch = Z*m/2;            // pitch circle radius
  var Rb = Rpitch*Math.cos(phi*Math.PI/180);  // base circle radius
  var Ra = Rpitch + addendum;    // tip (addendum) circle radius
  var Rroot = Rpitch - dedendum; // root circle radius
  var fRad = 1.5*clearance; // fillet radius, max 1.5*clearance
  var Rf = Math.sqrt((Rroot+fRad)*(Rroot+fRad)-(fRad*fRad)); // radius at top of fillet
  if (Rb < Rf)
    Rf = Rroot+clearance;
  // ****** calculate angles (all in radians)
  var pitchAngle = 2*Math.PI/Z;  // angle subtended by whole tooth (rads)
  var baseToPitchAngle = genInvolutePolar(Rb, Rpitch);
  var pitchToFilletAngle = baseToPitchAngle;  // profile starts at base circle
  if (Rf > Rb)                   // start profile at top of fillet (if its greater)
    pitchToFilletAngle -= genInvolutePolar(Rb, Rf);
  var filletAngle = Math.atan(fRad/(fRad+Rroot));  // radians
  // ****** generate Higuchi involute approximation
  var fe = 1;                    // fraction of profile length at end of approx
  var fs = 0.01;                 // fraction of length offset from base to avoid singularity
  if (Rf > Rb)
    fs = (Rf*Rf-Rb*Rb)/(Ra*Ra-Rb*Rb);  // offset start to top of fillet
  // approximate in 2 sections, split 25% along the involute
  var fm = fs+(fe-fs)/4;         // fraction of length at junction (25% along profile)
  var dedBz = involuteBezCoeffs(m, Z, phi, 3, fs, fm);
  var addBz = involuteBezCoeffs(m, Z, phi, 3, fm, fe);
  // join the 2 sets of coeffs (skip duplicate mid point)
  var inv = dedBz.concat(addBz.slice(1));
  //create the back profile of tooth (mirror image)
  var invR = [];                // involute profile along back of tooth
  for (var pt, i=0; i<inv.length; i++)
  {
    // rotate all points to put pitch point at y = 0
    pt = rotate(inv[i], -baseToPitchAngle-pitchAngle/4);
    inv[i] = pt;
    // generate the back of tooth profile nodes, mirror coords in X axis
    invR[i] = {x:pt.x, y:-pt.y};
  }
  // ****** calculate section junction points R=back of tooth, Next=front of next tooth)
  var fillet = toCartesian(Rf, -pitchAngle/4-pitchToFilletAngle); // top of fillet
  var filletR = {x:fillet.x, y:-fillet.y};   // flip to make same point on back of tooth
  var rootR = toCartesian(Rroot, pitchAngle/4+pitchToFilletAngle+filletAngle);
  var rootNext = toCartesian(Rroot, 3*pitchAngle/4-pitchToFilletAngle-filletAngle);
  var filletNext = rotate(fillet, pitchAngle);  // top of fillet, front of next tooth
  // ****** create the drawing command data array for the tooth
  var data = [];
  data.push("M", fillet.x, fillet.y);           // start at top of fillet
  if (Rf < Rb)
    data.push("L", inv[0].x, inv[0].y);         // line from fillet up to base circle
  data.push("C", inv[1].x, inv[1].y, inv[2].x, inv[2].y, inv[3].x, inv[3].y,
                 inv[4].x, inv[4].y, inv[5].x, inv[5].y, inv[6].x, inv[6].y);
  data.push("A", Ra, Ra, 0, 0, 0, invR[6].x, invR[6].y); // arc across addendum circle
  data.push("C", invR[5].x, invR[5].y, invR[4].x, invR[4].y, invR[3].x, invR[3].y,
                 invR[2].x, invR[2].y, invR[1].x, invR[1].y, invR[0].x, invR[0].y);
  if (Rf < Rb)
    data.push("L", filletR.x, filletR.y);       // line down to top of fillet
  if (rootNext.y > rootR.y)    // is there a section of root circle between fillets?
  {
    data.push("A", fRad, fRad, 0, 0, 1, rootR.x, rootR.y);// back fillet
    data.push("A", Rroot, Rroot, 0, 0, 0, rootNext.x, rootNext.y); // root circle arc
  }
  data.push("A", fRad, fRad, 0, 0, 1, filletNext.x, filletNext.y);

  return data;  // return an array of Cango (SVG) format draw commands
}

/*----------------------------------------------------------
  createIntGearTooth
  Create an array of drawing commands and their coordinates
  to draw a single internal (ring)gear tooth based on a
  circle involute using the metric gear standards.

  Requires Cango graphics library Rev 2.08 or later
 ----------------------------------------------------------*/
function createIntGearTooth(module, teeth, pressureAngle)
{
  function genInvolutePolar(Rb, R)  // Rb = base circle radius
  {
    // returns the involute angle as function of radius R.
    return (Math.sqrt(R*R - Rb*Rb)/Rb) - Math.acos(Rb/R);
  }

  function rotate(pt, rads)  // rotate pt by rads radians about origin
  {
    var sinA = Math.sin(rads);
    var cosA = Math.cos(rads);
    return {x: pt.x*cosA - pt.y*sinA,
            y: pt.x*sinA + pt.y*cosA};
  }

  function toCartesian(radius, angle)   // convert polar coords to cartesian
  {
    return {x: radius*Math.cos(angle),
            y: radius*Math.sin(angle)};
  }
  // ****** gear specifications
  var m = module;               // Module = mm of pitch diameter per tooth
  var Z = teeth;                // Number of teeth
  var phi = pressureAngle || 20;// pressure angle (degrees)
  var addendum = 0.6*m;         // pitch circle to tip circle (ref G.M.Maitra)
  var dedendum = 1.25*m;        // pitch circle to root radius, sets clearance
  // Calculate radii
  var Rpitch = Z*m/2;           // pitch radius
  var Rb = Rpitch*Math.cos(phi*Math.PI/180);  // base radius
  var Ra = Rpitch - addendum;   // addendum radius
  var Rroot = Rpitch + dedendum;// root radius
  var clearance = 0.25*m;       // gear dedendum - pinion addendum
  var Rf = Rroot - clearance;   // radius of top of fillet (end of profile)
  var fRad = 1.5*clearance;     // fillet radius, 1 .. 1.5*clearance
  // ****** calculate subtended angles
  var pitchAngle = 2*Math.PI/Z;  // angle between teeth (rads)
  var baseToPitchAngle = genInvolutePolar(Rb, Rpitch);
  var tipToPitchAngle = baseToPitchAngle;   // profile starts from base circle
  if (Ra > Rb)
    tipToPitchAngle -= genInvolutePolar(Rb, Ra);  // start profile from addendum
  var pitchToFilletAngle = genInvolutePolar(Rb, Rf) - baseToPitchAngle;
  var filletAngle = 1.414*clearance/Rf; // to make fillet tangential to root
  // ****** generate Higuchi involute approximation
  var fe = 1;                   // fraction of involute length at end of approx (fillet circle)
  var fs = 0.01                 // fraction of length offset from base to avoid singularity
  if (Ra > Rb)
    fs = (Ra*Ra-Rb*Rb)/(Rf*Rf-Rb*Rb);    // start profile from addendum (tip circle)
  // approximate in 2 sections, split 25% along the profile
  var fm = fs+(fe-fs)/4;        //
  var addBz = involuteBezCoeffs(m, Z, phi, 3, fs, fm);
  var dedBz = involuteBezCoeffs(m, Z, phi, 3, fm, fe);
  // join the 2 sets of coeffs (skip duplicate mid point)
  var invR = addBz.concat(dedBz.slice(1));
  //create the front profile of tooth (mirror image)
  var inv = [];         // back involute profile
  for (var pt, i=0; i<invR.length; i++)
  {
    // rotate involute to put center of tooth at y = 0
    pt = rotate(invR[i], pitchAngle/4-baseToPitchAngle);
    invR[i] = pt;
    // generate the back of tooth profile, flip Y coords
    inv[i] = {x:pt.x, y:-pt.y};
  }

  // ****** calculate coords of section junctions
  var fillet = {x:inv[6].x, y:inv[6].y};    // top of fillet, front of tooth
  var tip = toCartesian(Ra, -pitchAngle/4+tipToPitchAngle);  // tip, front of tooth
  var tipR = {x:tip.x, y:-tip.y};  // addendum, back of tooth
  var rootR = toCartesian(Rroot, pitchAngle/4+pitchToFilletAngle+filletAngle);
  var rootNext = toCartesian(Rroot, 3*pitchAngle/4-pitchToFilletAngle-filletAngle);
  var filletNext = rotate(fillet, pitchAngle);  // top of fillet, front of next tooth

  // ****** create the drawing command data array for the tooth
  var data = [];
  data.push("M", inv[6].x, inv[6].y);  // start at top of front profile
  data.push("C", inv[5].x, inv[5].y, inv[4].x, inv[4].y, inv[3].x, inv[3].y,
                 inv[2].x, inv[2].y, inv[1].x, inv[1].y, inv[0].x, inv[0].y);
  if (Ra < Rb)
    data.push("L", tip.x, tip.y);  // line from end of involute to addendum (tip)
  data.push("A", Ra, Ra, 0, 0, 0, tipR.x, tipR.y); // arc across tip circle
  if (Ra < Rb)
    data.push("L", invR[0].x, invR[0].y);  // line from addendum to start of involute
  data.push("C", invR[1].x, invR[1].y, invR[2].x, invR[2].y, invR[3].x, invR[3].y,
                 invR[4].x, invR[4].y, invR[5].x, invR[5].y, invR[6].x, invR[6].y);
  if (rootR.y < rootNext.y)    // there is a section of root circle between fillets
  {
    data.push("A", fRad, fRad, 0, 0, 0, rootR.x, rootR.y); // fillet on back of tooth
    data.push("A", Rroot, Rroot, 0, 0, 0, rootNext.x, rootNext.y); // root circle arc
  }
  data.push("A", fRad, fRad, 0, 0, 0, filletNext.x, filletNext.y); // fillet on next

  return data;  // return an array of Cango (SVG) format draw commands
}
