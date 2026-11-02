<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>HTML5 캔버스 전체 화면 불꽃놀이 효과 from https://github.com/sunyctf/js-effects </title>

    <style>
        /* basic styles for black background and crosshair cursor | 黑色背景和十字光标的基本样式 */
        body {
            background: rgba(255, 255, 255, 0.2);
            margin: 0;
        }

        canvas {
            cursor: crosshair;
            display: block;
        }
    </style>

</head>
<body>

<canvas id="canvas"></canvas>

<script>
    // when animating on canvas, it is best to use requestAnimationFrame instead of setTimeout or setInterval
    // 在画布上设置动画时，最好使用requestAnimationFrame而不是setTimeout或setInterval
    // not supported in all browsers though and sometimes needs a prefix, so we need a shim
    // 但并非所有浏览器都支持，有时需要前缀，因此我们需要垫片
    window.requestAnimFrame = (function () {
        // 浏览器兼容写法
        // var requestAnimationFrame = window.requestAnimationFrame || window.mozRequestAnimationFrame ||  window.webkitRequestAnimationFrame || window.msRequestAnimationFrame
        // var cancelAnimationFrame = window.cancelAnimationFrame || window.mozCancelAnimationFrame
        return window.requestAnimationFrame ||
            window.webkitRequestAnimationFrame ||
            window.mozRequestAnimationFrame ||
            function (callback) {
                // 时间间隔为什么是1000/60,这是因为大多数屏幕渲染的时间间隔是每秒60帧。
                window.setTimeout(callback, 1000 / 60);
            };
    })();

    // now we will setup our basic variables for the demo
    var canvas = document.getElementById('canvas'),
        ctx = canvas.getContext('2d'),
        // full screen dimensions
        cw = window.innerWidth,
        ch = window.innerHeight,
        // firework collection
        fireworks = [],
        // particle collection
        particles = [],
        // starting hue
        hue = 120,
        // when launching fireworks with a click, too many get launched at once without a limiter, one launch per 5 loop ticks
        limiterTotal = 5,
        limiterTick = 0,
        // this will time the auto launches of fireworks, one launch per 80 loop ticks
        timerTotal = 80,
        timerTick = 0,
        mousedown = false,
        // mouse x coordinate,
        mx,
        // mouse y coordinate
        my;

    // set canvas dimensions
    canvas.width = cw;
    canvas.height = ch;

    // now we are going to setup our function placeholders for the entire demo

    // get a random number within a range
    function random(min, max) {
        return Math.random() * (max - min) + min;
    }

    // calculate the distance between two points
    function calculateDistance(p1x, p1y, p2x, p2y) {
        var xDistance = p1x - p2x,
            yDistance = p1y - p2y;
        return Math.sqrt(Math.pow(xDistance, 2) + Math.pow(yDistance, 2));
    }

    // create firework
    function Firework(sx, sy, tx, ty) {
        // actual coordinates
        this.x = sx;
        this.y = sy;
        // starting coordinates
        this.sx = sx;
        this.sy = sy;
        // target coordinates
        this.tx = tx;
        this.ty = ty;
        // distance from starting point to target
        this.distanceToTarget = calculateDistance(sx, sy, tx, ty);
        this.distanceTraveled = 0;
        // track the past coordinates of each firework to create a trail effect, increase the coordinate count to create more prominent trails
        this.coordinates = [];
        this.coordinateCount = 3;
        // populate initial coordinate collection with the current coordinates
        while (this.coordinateCount--) {
            this.coordinates.push([this.x, this.y]);
        }
        this.angle = Math.atan2(ty - sy, tx - sx);
        this.speed = 2;
        this.acceleration = 1.05;
        this.brightness = random(50, 70);
        // circle target indicator radius
        this.targetRadius = 1;
    }

    // update firework
    Firework.prototype.update = function (index) {
        // remove last item in coordinates array
        this.coordinates.pop();
        // add current coordinates to the start of the array
        this.coordinates.unshift([this.x, this.y]);

        // cycle the circle target indicator radius
        if (this.targetRadius < 8) {
            this.targetRadius += 0.3;
        } else {
            this.targetRadius = 1;
        }

        // speed up the firework
        this.speed *= this.acceleration;

        // get the current velocities based on angle and speed
        var vx = Math.cos(this.angle) * this.speed,
            vy = Math.sin(this.angle) * this.speed;
        // how far will the firework have traveled with velocities applied?
        this.distanceTraveled = calculateDistance(this.sx, this.sy, this.x + vx, this.y + vy);

        // if the distance traveled, including velocities, is greater than the initial distance to the target, then the target has been reached
        if (this.distanceTraveled >= this.distanceToTarget) {
            createParticles(this.tx, this.ty);
            // remove the firework, use the index passed into the update function to determine which to remove
            fireworks.splice(index, 1);
        } else {
            // target not reached, keep traveling
            this.x += vx;
            this.y += vy;
        }
    }

    // draw firework
    Firework.prototype.draw = function () {
        ctx.beginPath();
        // move to the last tracked coordinate in the set, then draw a line to the current x and y
        ctx.moveTo(this.coordinates[this.coordinates.length - 1][0], this.coordinates[this.coordinates.length - 1][1]);
        ctx.lineTo(this.x, this.y);
        ctx.strokeStyle = 'hsl(' + hue + ', 100%, ' + this.brightness + '%)';
        ctx.stroke();

        ctx.beginPath();
        // draw the target for this firework with a pulsing circle
        ctx.arc(this.tx, this.ty, this.targetRadius, 0, Math.PI * 2);
        ctx.stroke();
    }

    // create particle
    function Particle(x, y) {
        this.x = x;
        this.y = y;
        // track the past coordinates of each particle to create a trail effect, increase the coordinate count to create more prominent trails
        this.coordinates = [];
        this.coordinateCount = 5;
        while (this.coordinateCount--) {
            this.coordinates.push([this.x, this.y]);
        }
        // set a random angle in all possible directions, in radians
        this.angle = random(0, Math.PI * 2);
        this.speed = random(1, 10);
        // friction will slow the particle down
        this.friction = 0.95;
        // gravity will be applied and pull the particle down
        this.gravity = 1;
        // set the hue to a random number +-20 of the overall hue variable
        this.hue = random(hue - 20, hue + 20);
        this.brightness = random(50, 80);
        this.alpha = 1;
        // set how fast the particle fades out
        this.decay = random(0.015, 0.03);
    }

    // update particle
    Particle.prototype.update = function (index) {
        // remove last item in coordinates array
        this.coordinates.pop();
        // add current coordinates to the start of the array
        this.coordinates.unshift([this.x, this.y]);
        // slow down the particle
        this.speed *= this.friction;
        // apply velocity
        this.x += Math.cos(this.angle) * this.speed;
        this.y += Math.sin(this.angle) * this.speed + this.gravity;
        // fade out the particle
        this.alpha -= this.decay;

        // remove the particle once the alpha is low enough, based on the passed in index
        if (this.alpha <= this.decay) {
            particles.splice(index, 1);
        }
    }


    // draw particle
    Particle.prototype.draw = function () {

        ctx.beginPath();
        // move to the last tracked coordinates in the set, then draw a line to the current x and y
        ctx.moveTo(this.coordinates[this.coordinates.length - 1][0], this.coordinates[this.coordinates.length - 1][1]);
        ctx.lineTo(this.x, this.y);
        ctx.strokeStyle = 'hsla(' + this.hue + ', 100%, ' + this.brightness + '%, ' + this.alpha + ')';
        ctx.stroke();


    }

    // create particle group/explosion
    function createParticles(x, y) {
        // increase the particle count for a bigger explosion, beware of the canvas performance hit with the increased particles though
        var particleCount = 30;
        while (particleCount--) {
            particles.push(new Particle(x, y));
        }
    }

    var slogan = [
        "뜻이 있는 곳에 길이 있다.",
        "마음의 크기만큼 무대도 커진다.",
        "나를 믿고, 미래를 믿자.",
        "탁월함을 좇고, 평범함을 넘어서자.",
        "꿈꿀 용기, 실패할 용기.",
        "불가능은 없다. 아직 떠올리지 못했을 뿐.",
        "꾸준히, 용감하게 앞으로.",
        "나를 가로막는 건 내 상상력뿐이다.",
        "하루하루가 성장의 기회다.",
        "성공도 끝이 아니고, 실패도 끝이 아니다.",
        "하는 일을 사랑해야 최고가 될 수 있다.",
        "매일이 새로운 시작이다.",
        "챔피언은 성공할 때까지 포기하지 않는다.",
        "성공은 한 번의 사건이 아니라 삶의 방식이다.",
        "바닷가에 앉아만 있어서는 바다를 건널 수 없다.",
        "내가 될 사람은 내가 되기로 결심한 사람뿐이다.",
        "내일을 가로막는 유일한 한계는 오늘의 의심이다.",
        "행복하게 살고 싶다면 삶을 목표와 이어라.",
        "미래는 자기 꿈의 아름다움을 믿는 사람의 것이다.",
        "성공은 매일 반복한 작은 노력이 쌓인 결과다.",
        "꿈꿀 수 있다면 이룰 수 있다.",
        "할 수 있다고 믿으면 이미 절반은 해낸 것이다.",
        "미래를 예측하는 가장 좋은 방법은 미래를 만드는 것이다.",
        "꿈을 이루는 유일한 방법은 깨어나는 것이다.",
        "성공은 돈을 버는 것을 넘어 영향을 주는 것이다.",
        "성공에 이르는 유일한 길은 실패를 거듭하는 것이다.",
        "성공을 보장하는 것은 노력뿐이다.",
        "꿈은 성공의 출발점이다.",
        "모든 실패는 배움의 기회다.",
        "성공은 끊임없는 노력과 끈기에서 나온다.",
        "나를 믿고, 기적을 믿자.",
        "꿈은 행동의 촉매제다.",
        "성공은 마음의 힘에서 나온다.",
        "성공할 때까지 절대 포기하지 말자.",
        "모든 시도는 성공을 향한 한 걸음이다.",
        "성공은 끊임없이 자신을 넘어설 때 찾아온다.",
        "꿈은 앞으로 나아가는 힘이다.",
        "성공은 끊임없는 혁신에서 나온다.",
        "꿈은 가능성을 여는 시작이다.",
        "성공은 끊임없는 배움과 성장에서 나온다.",
        "꿈은 삶의 색깔이다.",
        "성공은 끊임없이 자신에게 도전할 때 찾아온다."
    ];
    var text = slogan[Math.floor(Math.random() * slogan.length)];

    // main demo loop
    function loop() {
        // this function will run endlessly with requestAnimationFrame
        requestAnimFrame(loop);

        // increase the hue to get different colored fireworks over time
        hue += 0.5;

        // normally, clearRect() would be used to clear the canvas
        // we want to create a trailing effect though
        // setting the composite operation to destination-out will allow us to clear the canvas at a specific opacity, rather than wiping it entirely
        ctx.globalCompositeOperation = 'destination-out';
        // decrease the alpha property to create more prominent trails
        ctx.fillStyle = 'rgba(0, 0, 0, 0.5)';
        ctx.fillRect(0, 0, cw, ch);
        // change the composite operation back to our main mode
        // lighter creates bright highlight points as the fireworks and particles overlap each other
        ctx.globalCompositeOperation = 'lighter';

        // var text = "成功AC!!";
        ctx.font = "50px sans-serif";
        var textData = ctx.measureText(text);
        ctx.fillStyle = "rgba(" + parseInt(random(0, 255)) + "," + parseInt(random(0, 255)) + "," + parseInt(random(0, 255)) + ",0.6)";
        ctx.fillText(text, cw / 2 - textData.width / 2, ch / 2);

        // loop over each firework, draw it, update it
        var i = fireworks.length;
        while (i--) {
            fireworks[i].draw();
            fireworks[i].update(i);
        }

        // loop over each particle, draw it, update it
        var i = particles.length;
        while (i--) {
            particles[i].draw();
            particles[i].update(i);
        }

        // launch fireworks automatically to random coordinates, when the mouse isn't down
        if (timerTick >= timerTotal) {
            if (!mousedown) {
                // start the firework at the bottom middle of the screen, then set the random target coordinates, the random y coordinates will be set within the range of the top half of the screen

                for (var h = 0; h < 50; h++) {
                    fireworks.push(new Firework(cw / 2, ch / 2, random(0, cw), random(0, ch)));
                }


                timerTick = 0;
            }
        } else {
            timerTick++;
        }

        // limit the rate at which fireworks get launched when mouse is down
        if (limiterTick >= limiterTotal) {
            if (mousedown) {
                // start the firework at the bottom middle of the screen, then set the current mouse coordinates as the target
                fireworks.push(new Firework(cw / 2, ch / 2, mx, my));
                limiterTick = 0;
            }
        } else {
            limiterTick++;
        }
    }

    // mouse event bindings
    // update the mouse coordinates on mousemove
    canvas.addEventListener('mousemove', function (e) {
        mx = e.pageX - canvas.offsetLeft;
        my = e.pageY - canvas.offsetTop;
    });

    // toggle mousedown state and prevent canvas from being selected
    canvas.addEventListener('mousedown', function (e) {
        e.preventDefault();
        mousedown = true;
    });

    canvas.addEventListener('mouseup', function (e) {
        e.preventDefault();
        mousedown = false;
    });

    // once the window loads, we are ready for some fireworks!
    window.onload = loop;

</script>
</body>
</html>
