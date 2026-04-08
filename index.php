<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simuze — Coming Soon</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: #0a0a0a;
            color: #fff;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            overflow: hidden;
        }
        .logo { width: 220px; margin-bottom: 3rem; opacity: 0.95; }
        @media (max-width: 480px) { .logo { width: 160px; margin-bottom: 2rem; } }
        .countdown {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }
        @media (max-width: 480px) { .countdown { gap: .8rem; } }
        .countdown-unit {
            text-align: center;
            min-width: 70px;
        }
        @media (max-width: 480px) { .countdown-unit { min-width: 55px; } }
        .countdown-num {
            font-size: 3rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            line-height: 1;
            font-variant-numeric: tabular-nums;
        }
        @media (max-width: 480px) { .countdown-num { font-size: 2rem; } }
        .countdown-label {
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .12em;
            color: rgba(255,255,255,0.4);
            margin-top: .5rem;
        }
        .tagline {
            color: rgba(255,255,255,0.35);
            font-size: .9rem;
            letter-spacing: .04em;
        }
    </style>
</head>
<body>
    <svg class="logo" viewBox="0 0 336 60" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="2"><path d="M57.321 26.708c0-1.823-1.595-3.304-3.532-3.304H8.782c2.735-8.774 10.824-14.926 20.282-14.926 4.33 0 8.432 1.253 11.964 3.646 1.709 1.253 4.102.798 5.241-.912 1.139-1.709.684-3.988-1.025-5.241C40.458 2.667 34.875.958 29.064.958 9.466.958.692 18.733.692 26.139c0 1.139-.114 4.216 3.988 4.216h45.691c-.228 11.394-10.141 20.624-22.333 20.624-7.52 0-14.471-3.532-18.573-9.343-1.139-1.595-3.304-1.937-4.9-.912-1.595 1.139-1.937 3.304-.798 4.9 5.469 7.634 14.585 12.306 24.27 12.306 16.18 0 29.397-12.648 29.397-28.144l-.114-3.076z" fill="#fff"/><path d="M66.208 57.928c-2.165 0-3.76-1.709-3.76-3.646V4.603c0-1.937 1.595-3.646 3.76-3.646 2.051 0 3.76 1.709 3.76 3.646v49.679c0 1.937-1.709 3.646-3.76 3.646z" fill="#fff"/><path d="M133.32.957c-7.634 0-14.243 3.874-18.231 9.685C111.101 4.831 104.492.957 96.858.957c-12.078 0-21.877 9.799-21.877 21.877v31.334c0 2.051 1.595 3.76 3.646 3.76 2.051 0 3.76-1.709 3.76-3.76V22.834c0-7.976 6.495-14.471 14.471-14.471 7.976 0 14.471 6.495 14.471 14.471v31.334c0 2.051 1.709 3.76 3.76 3.76 2.051 0 3.76-1.709 3.76-3.76V22.834c0-7.976 6.495-14.471 14.471-14.471 7.976 0 14.471 6.495 14.471 14.471v31.334c0 2.051 1.709 3.76 3.76 3.76 2.051 0 3.646-1.709 3.646-3.76V22.834c0-12.078-9.799-21.877-21.877-21.877z" fill="#fff"/><path d="M213.535.957c-2.051 0-3.76 1.709-3.76 3.76v24.725c0 11.622-9.457 21.079-21.079 21.079-11.508 0-20.965-9.457-20.965-21.079V4.717c0-2.051-1.709-3.76-3.76-3.76-2.051 0-3.76 1.709-3.76 3.76v24.725c0 15.724 12.875 28.486 28.486 28.486 15.724 0 28.486-12.762 28.486-28.486V4.717c0-2.051-1.595-3.76-3.646-3.76z" fill="#fff"/><path d="M271.19 2.438h-46.944c-1.367 0-2.507 1.481-2.507 3.304 0 1.709 1.139 3.19 2.507 3.19h39.424s-38.968 38.171-42.159 41.361c-2.621 2.621-.798 6.153 1.367 6.153h48.995c1.367 0 2.507-1.481 2.507-3.19 0-1.823-1.139-3.19-2.507-3.19h-41.475l42.386-41.703c1.595-1.481 1.367-5.925-1.595-5.925z" fill="#fff"/><path d="M305.828.957c-15.724 0-28.372 12.875-28.372 28.599 0 15.61 12.648 28.372 28.372 28.372 10.141 0 19.484-5.469 24.498-14.243 1.025-1.823.456-4.102-1.367-5.013-1.823-1.025-4.102-.342-5.013 1.253-3.76 6.495-10.597 10.597-18.117 10.597-11.736 0-20.965-9.457-20.965-20.965 0-11.736 9.229-21.079 20.965-21.079 10.597 0 19.142 7.748 20.624 18.003h-27.346c-1.823 0-3.304 1.481-3.304 3.532 0 1.823 1.481 3.304 3.304 3.304h31.334c1.709 0 3.646-1.823 3.646-3.76C334.086 13.832 321.438.957 305.828.957z" fill="#fff"/></svg>

    <div class="countdown" id="countdown">
        <div class="countdown-unit"><div class="countdown-num" id="days">--</div><div class="countdown-label">Days</div></div>
        <div class="countdown-unit"><div class="countdown-num" id="hours">--</div><div class="countdown-label">Hours</div></div>
        <div class="countdown-unit"><div class="countdown-num" id="mins">--</div><div class="countdown-label">Minutes</div></div>
        <div class="countdown-unit"><div class="countdown-num" id="secs">--</div><div class="countdown-label">Seconds</div></div>
    </div>

    <p class="tagline">Launching June 1, 2026</p>

    <script>
        (function() {
            var target = new Date('2026-06-01T00:00:00').getTime();
            function update() {
                var diff = target - Date.now();
                if (diff <= 0) {
                    document.getElementById('countdown').innerHTML = '<div class="countdown-num" style="font-size:2rem;">We are live!</div>';
                    return;
                }
                var d = Math.floor(diff / 86400000);
                var h = Math.floor((diff % 86400000) / 3600000);
                var m = Math.floor((diff % 3600000) / 60000);
                var s = Math.floor((diff % 60000) / 1000);
                document.getElementById('days').textContent = d;
                document.getElementById('hours').textContent = h < 10 ? '0' + h : h;
                document.getElementById('mins').textContent = m < 10 ? '0' + m : m;
                document.getElementById('secs').textContent = s < 10 ? '0' + s : s;
            }
            update();
            setInterval(update, 1000);
        })();
    </script>
</body>
</html>
