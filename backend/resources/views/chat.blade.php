<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#13a06a">
    <title>Boshpana.ai</title>
    <style>
        :root {
            --page: #f1f6f3;
            --surface: #ffffff;
            --text: #1f2a33;
            --slate: #1c3344;
            --text-soft: #5a6b62;
            --text-faint: #8a9a90;
            --border: #e2ebe5;
            --border-strong: #cdd8d0;
            --green: #13a06a;
            --green-dark: #0e8659;
            --green-soft: #e6f3ec;
            --green-soft-2: #f1f7f3;
            --green-line: #cfe7da;
            --input-bg: #eaf1ed;
            --grad: linear-gradient(135deg, #13a06a 0%, #0e8659 100%);
            --grad-soft: linear-gradient(135deg, #e6f3ec 0%, #f1f7f3 100%);
            --shadow-sm: 0 1px 2px rgba(28,51,68,.08), 0 1px 3px rgba(28,51,68,.06);
            --shadow-md: 0 2px 8px rgba(28,51,68,.12), 0 1px 3px rgba(28,51,68,.08);
            --shadow-lg: 0 8px 28px rgba(28,51,68,.18);
            --radius: 24px;
            --maxw: 760px;
            --font: "Google Sans", "Google Sans Text", system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
        }

        * { box-sizing: border-box; }
        button, a, textarea, input[type=range] { touch-action: manipulation; }
        html, body { height: 100%; }
        body {
            margin: 0;
            font-family: var(--font);
            color: var(--text);
            background: var(--page);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            overscroll-behavior: none;
            height: 100vh;       /* fallback */
            height: 100svh;
            height: 100dvh;      /* shrinks with the mobile keyboard / URL bar */
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
        }

        /* ---------- Top bar ---------- */
        .topbar {
            flex: 0 0 auto;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            border-bottom: 1px solid transparent;
            transition: border-color .2s ease;
            z-index: 30;
            background: var(--page);
        }
        .topbar.scrolled { border-bottom-color: var(--border); }

        .wordmark {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 20px;
            font-weight: 600;
            letter-spacing: -0.2px;
            user-select: none;
        }
        .wordmark .brand-dot {
            width: 12px; height: 12px;
            flex: 0 0 auto;
            border-radius: 50%;
            background: var(--green);
            box-shadow: 0 1px 3px rgba(19,160,106,.45);
        }
        .wordmark .brand { color: var(--slate); font-weight: 600; }

        .topbar .spacer { flex: 1; }

        .lang-switch {
            display: inline-flex;
            background: var(--green-soft);
            border-radius: 999px;
            padding: 3px;
            gap: 2px;
        }
        .lang-switch button {
            border: none;
            background: transparent;
            color: var(--text-soft);
            font: inherit;
            font-size: 13px;
            font-weight: 500;
            padding: 5px 11px;
            border-radius: 999px;
            cursor: pointer;
            transition: background .15s, color .15s;
            text-transform: uppercase;
        }
        .lang-switch button:hover { color: var(--text); }
        .lang-switch button.active {
            background: var(--surface);
            color: var(--green-dark);
            box-shadow: var(--shadow-sm);
        }

        .newchat-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            border: 1px solid var(--border-strong);
            background: var(--surface);
            color: var(--text);
            font: inherit;
            font-size: 14px;
            font-weight: 500;
            padding: 8px 15px;
            border-radius: 999px;
            cursor: pointer;
            transition: background .15s, box-shadow .15s, border-color .15s;
            white-space: nowrap;
        }
        .newchat-btn:hover { background: var(--green-soft-2); box-shadow: var(--shadow-sm); border-color: var(--green-line); }
        .newchat-btn svg { width: 16px; height: 16px; color: var(--green-dark); }

        /* ---------- Scroll / conversation ---------- */
        .scroll {
            flex: 1 1 auto;
            overflow-y: auto;
            overflow-x: hidden;
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior-y: contain;
            position: relative;
        }
        .scroll::-webkit-scrollbar { width: 10px; }
        .scroll::-webkit-scrollbar-thumb { background: #cdd8d0; border-radius: 99px; border: 3px solid var(--page); }
        .scroll::-webkit-scrollbar-thumb:hover { background: #b6c5bb; }

        .column {
            max-width: var(--maxw);
            margin: 0 auto;
            padding: 18px 22px 28px;
            min-height: 100%;
            display: flex;
            flex-direction: column;
        }

        /* ---------- Empty / greeting state ---------- */
        .greeting {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 24px 0 40px;
            animation: fadeIn .5s ease both;
        }
        .greeting h1 {
            font-size: clamp(30px, 6vw, 50px);
            line-height: 1.12;
            font-weight: 600;
            margin: 0 0 6px;
            letter-spacing: -0.5px;
            background: var(--grad);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            width: fit-content;
        }
        .greeting h2 {
            font-size: clamp(24px, 5vw, 40px);
            line-height: 1.15;
            font-weight: 500;
            margin: 0 0 26px;
            color: #aebfb5;
            letter-spacing: -0.5px;
        }
        .suggestions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0,1fr));
            gap: 12px;
        }
        .suggest-card {
            text-align: left;
            border: 1px solid var(--border);
            background: var(--surface);
            border-radius: 16px;
            padding: 16px 16px 44px;
            position: relative;
            cursor: pointer;
            font: inherit;
            font-size: 14.5px;
            line-height: 1.4;
            color: var(--text);
            transition: background .18s, box-shadow .18s, transform .18s, border-color .18s;
            min-height: 92px;
        }
        .suggest-card:hover { background: var(--green-soft-2); box-shadow: var(--shadow-sm); transform: translateY(-1px); border-color: var(--green-line); }
        .suggest-card .ico {
            position: absolute;
            bottom: 12px; right: 12px;
            width: 30px; height: 30px;
            display: grid; place-items: center;
            background: var(--surface);
            border-radius: 50%;
            box-shadow: var(--shadow-sm);
            font-size: 16px;
        }

        /* ---------- Messages ---------- */
        .messages { display: flex; flex-direction: column; gap: 22px; padding-top: 8px; }

        .msg { display: flex; gap: 14px; animation: msgIn .38s cubic-bezier(.22,.61,.36,1) both; }
        @keyframes msgIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }

        .msg .avatar {
            flex: 0 0 auto;
            width: 30px; height: 30px;
            border-radius: 50%;
            display: grid; place-items: center;
            margin-top: 2px;
        }
        .msg.ai .avatar { background: var(--green); }
        .msg.ai .avatar svg { width: 17px; height: 17px; }

        .msg .body { min-width: 0; flex: 1; }

        .msg.ai .bubble {
            font-size: 15.5px;
            line-height: 1.62;
            color: var(--text);
            white-space: pre-wrap;
            word-wrap: break-word;
            padding-top: 3px;
        }

        .msg.user { flex-direction: row-reverse; }
        .msg.user .bubble {
            background: var(--green-soft);
            color: #0c3b27;
            padding: 12px 16px;
            border-radius: 20px 20px 6px 20px;
            font-size: 15px;
            line-height: 1.55;
            max-width: 86%;
            white-space: pre-wrap;
            word-wrap: break-word;
            box-shadow: var(--shadow-sm);
        }
        .msg.user .body { display: flex; flex-direction: column; align-items: flex-end; gap: 8px; }
        .msg.user .avatar {
            background: var(--green);
            color: #fff;
        }

        .user-media img {
            max-width: 240px;
            max-height: 260px;
            border-radius: 18px 18px 6px 18px;
            display: block;
            box-shadow: var(--shadow-sm);
            object-fit: cover;
        }
        .loc-meta {
            font-size: 12.5px;
            color: var(--green-dark);
            background: var(--green-soft);
            padding: 7px 11px;
            border-radius: 12px;
            font-weight: 500;
        }

        .voice-attach {
            display: flex; flex-direction: column; gap: 8px;
            max-width: 86%;
            background: var(--green-soft);
            padding: 10px 12px 12px;
            border-radius: 20px 20px 6px 20px;
            box-shadow: var(--shadow-sm);
        }
        .voice-attach .voice-head {
            display: flex; align-items: center; gap: 7px;
            font-size: 12px; color: var(--green-dark); font-weight: 600;
        }
        .voice-attach audio { width: 240px; max-width: 60vw; height: 38px; }
        .voice-attach .vtext { font-size: 15px; line-height: 1.5; color: #0c3b27; white-space: pre-wrap; }

        /* ---------- Chips ---------- */
        .chips { display: flex; flex-wrap: wrap; gap: 9px; margin-top: 14px; animation: msgIn .4s ease both; }
        .chip {
            border: 1px solid var(--green-line);
            background: var(--surface);
            color: var(--green-dark);
            font: inherit;
            font-size: 14px;
            font-weight: 500;
            padding: 9px 16px;
            border-radius: 999px;
            cursor: pointer;
            transition: background .15s, box-shadow .15s, transform .12s;
        }
        .chip:hover { background: var(--green-soft); box-shadow: var(--shadow-sm); }
        .chip:active { transform: scale(.97); }

        /* ---------- Guided interactive cards ---------- */
        .gcard {
            margin-top: 14px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 16px 16px 14px;
            box-shadow: var(--shadow-sm);
            max-width: 440px;
            animation: msgIn .4s ease both;
        }
        .gcard.locked { opacity: .9; }
        .gcard.locked .gchips, .gcard.locked .gcard-actions,
        .gcard.locked .gstepper, .gcard.locked .gswitch { pointer-events: none; }
        .gcard.locked .gchip:not(.selected) { opacity: .45; }
        .gcard.locked .gcard-actions { display: none; }

        .gcard-head { display: flex; align-items: center; gap: 12px; margin-bottom: 15px; }
        .gcard-ico { width: 40px; height: 40px; flex: 0 0 auto; border-radius: 12px; background: var(--green-soft); display: grid; place-items: center; font-size: 20px; }
        .gcard-title { font-size: 16px; font-weight: 600; color: var(--slate); line-height: 1.3; }
        .gcard-sub { font-size: 13px; color: var(--text-soft); margin-top: 2px; line-height: 1.4; }

        .gchips { display: flex; flex-wrap: wrap; gap: 9px; }
        .gchip {
            border: 1px solid var(--green-line);
            background: var(--surface);
            color: var(--text);
            font: inherit; font-size: 14px; font-weight: 500;
            padding: 9px 15px; border-radius: 999px; cursor: pointer;
            display: inline-flex; align-items: center; gap: 8px;
            transition: background .15s, border-color .15s, color .15s, box-shadow .15s, transform .1s;
        }
        .gchip:hover { background: var(--green-soft-2); box-shadow: var(--shadow-sm); }
        .gchip:active { transform: scale(.97); }
        .gchip.selected { background: var(--green); border-color: var(--green); color: #fff; box-shadow: var(--shadow-sm); }
        .gchip-multi .gcheck {
            width: 17px; height: 17px; border-radius: 50%;
            border: 1.5px solid var(--green-line);
            display: inline-grid; place-items: center;
            font-size: 11px; line-height: 1; color: transparent; transition: all .15s;
        }
        .gchip-multi.selected .gcheck { background: #fff; border-color: #fff; color: var(--green); }

        .gfield { display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 12px 0; border-top: 1px solid var(--border); }
        .gcard-body > .gfield:first-child { border-top: none; padding-top: 2px; }
        .gfield-label { font-size: 14.5px; font-weight: 500; color: var(--slate); }
        .gfield-sub { font-size: 12.5px; color: var(--text-soft); margin-top: 2px; }

        .gstepper { display: flex; align-items: center; gap: 4px; background: var(--green-soft-2); border-radius: 999px; padding: 4px; flex: 0 0 auto; }
        .gstep-btn { width: 32px; height: 32px; border: none; border-radius: 50%; background: var(--surface); color: var(--green-dark); font-size: 19px; font-weight: 600; line-height: 1; cursor: pointer; display: grid; place-items: center; box-shadow: var(--shadow-sm); transition: background .15s, transform .1s; }
        .gstep-btn:hover:not(:disabled) { background: var(--green-soft); }
        .gstep-btn:active:not(:disabled) { transform: scale(.9); }
        .gstep-btn:disabled { opacity: .4; cursor: not-allowed; box-shadow: none; }
        .gstep-val { min-width: 28px; text-align: center; font-size: 16px; font-weight: 600; color: var(--slate); }

        .gswitch { width: 46px; height: 28px; border: none; border-radius: 999px; background: #cdd8d0; cursor: pointer; position: relative; padding: 0; transition: background .2s; flex: 0 0 auto; }
        .gswitch.on { background: var(--green); }
        .gknob { position: absolute; top: 3px; left: 3px; width: 22px; height: 22px; border-radius: 50%; background: #fff; box-shadow: var(--shadow-sm); transition: left .2s; }
        .gswitch.on .gknob { left: 21px; }

        .gcard-actions { display: flex; gap: 10px; margin-top: 16px; }
        .gbtn { flex: 1; font: inherit; font-size: 14.5px; font-weight: 600; padding: 11px 18px; border-radius: 13px; cursor: pointer; transition: background .15s, box-shadow .15s, transform .1s; }
        .gbtn:active { transform: scale(.98); }
        .gbtn-primary { border: none; background: var(--green); color: #fff; box-shadow: var(--shadow-sm); }
        .gbtn-primary:hover { background: var(--green-dark); box-shadow: var(--shadow-md); }
        .gbtn-ghost { border: 1px solid var(--border-strong); background: var(--surface); color: var(--text); }
        .gbtn-ghost:hover { background: var(--green-soft-2); }

        .greeting.compact { flex: 0 0 auto; justify-content: flex-start; padding: 6px 0 14px; }
        .greeting.compact h1 { font-size: clamp(26px, 5vw, 38px); margin-bottom: 4px; }
        .greeting.compact h2 { font-size: clamp(20px, 4vw,28px); margin-bottom: 0; }
        .greeting.compact .suggestions { display: none; }

        /* ---------- Typing ---------- */
        .typing .dots { display: inline-flex; gap: 5px; align-items: center; height: 26px; padding-left: 2px; }
        .typing .dots span {
            width: 8px; height: 8px; border-radius: 50%;
            background: var(--green);
            animation: bounce 1.3s infinite ease-in-out;
        }
        .typing .dots span:nth-child(2) { animation-delay: .18s; }
        .typing .dots span:nth-child(3) { animation-delay: .36s; }
        @keyframes bounce { 0%,80%,100% { transform: translateY(0); opacity:.5; } 40% { transform: translateY(-6px); opacity:1; } }
        .msg.ai .avatar.pulsing { animation: avatarPulse 1.4s ease-in-out infinite; }
        @keyframes avatarPulse { 0%,100% { transform: scale(1); } 50% { transform: scale(1.08); } }

        /* ---------- Searching checklist (async poll) ---------- */
        @keyframes spin { to { transform: rotate(360deg); } }
        .checklist { display: flex; flex-direction: column; gap: 11px; padding-top: 4px; max-width: 100%; }
        .cl-row { display: flex; align-items: center; gap: 11px; }
        .cl-mark { width: 20px; height: 20px; flex: 0 0 auto; display: grid; place-items: center; }
        .cl-label { font-size: 15px; line-height: 1.2; color: var(--text-faint); transition: color .2s; }
        .cl-row.done .cl-label { color: var(--text-soft); }
        .cl-row.current .cl-label { color: var(--text); font-weight: 600; }
        .cl-check {
            width: 20px; height: 20px; border-radius: 50%;
            background: var(--green); color: #fff;
            display: grid; place-items: center; font-size: 12px; line-height: 1;
            animation: msgIn .25s ease both;
        }
        .cl-spin {
            width: 18px; height: 18px; flex: 0 0 auto;
            border: 2.5px solid var(--green-soft);
            border-top-color: var(--green);
            border-radius: 50%;
            animation: spin .8s linear infinite;
        }
        .cl-circle { width: 15px; height: 15px; border-radius: 50%; border: 2px solid var(--border-strong); }
        .s-progress { margin-top: 13px; max-width: 360px; }
        .s-counts { font-size: 13px; color: var(--text-soft); }

        /* ---------- Result cards ---------- */
        .results {
            display: grid;
            grid-template-columns: repeat(2, minmax(0,1fr));
            gap: 16px;
            margin: 6px 0 4px;
            animation: msgIn .45s ease both;
        }
        .card {
            border: 1px solid var(--border);
            border-radius: 18px;
            overflow: hidden;
            background: var(--surface);
            display: flex;
            flex-direction: column;
            transition: box-shadow .2s, transform .2s, border-color .2s;
        }
        .card:hover { box-shadow: var(--shadow-lg); transform: translateY(-2px); border-color: var(--green-line); }
        .card .thumb {
            position: relative;
            aspect-ratio: 16 / 10;
            background: var(--grad-soft);
            background-size: cover;
            background-position: center;
            display: grid; place-items: center;
        }
        .card .thumb .ph { font-size: 38px; opacity: .55; }
        .card .price-badge {
            position: absolute; left: 10px; bottom: 10px;
            background: rgba(255,255,255,.95);
            backdrop-filter: blur(4px);
            color: #0b3d2e;
            font-weight: 700;
            font-size: 16px;
            padding: 5px 11px;
            border-radius: 999px;
            box-shadow: var(--shadow-sm);
        }
        .card .score-badge {
            position: absolute; right: 10px; top: 10px;
            background: var(--grad);
            color: #fff;
            font-weight: 600;
            font-size: 12px;
            padding: 4px 9px;
            border-radius: 999px;
            box-shadow: var(--shadow-sm);
            display: inline-flex; align-items: center; gap: 4px;
        }
        .card .info { padding: 13px 14px 14px; display: flex; flex-direction: column; gap: 9px; flex: 1; }
        .card .title {
            font-size: 14.5px; font-weight: 500; line-height: 1.35; color: var(--text);
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
        }
        .card .meta { font-size: 13px; color: var(--text-soft); line-height: 1.5; }
        .card .foot { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-top: auto; padding-top: 4px; }
        .card .source-pill {
            font-size: 11.5px; font-weight: 600; color: var(--text-soft);
            background: var(--green-soft-2); padding: 4px 9px; border-radius: 999px;
        }
        .card .view-btn {
            display: inline-flex; align-items: center; gap: 6px;
            background: var(--green); color: #fff; text-decoration: none;
            font-size: 13px; font-weight: 500;
            padding: 7px 13px; border-radius: 999px;
            transition: background .15s, box-shadow .15s;
        }
        .card .view-btn:hover { background: var(--green-dark); box-shadow: var(--shadow-sm); }
        .card .view-btn svg { width: 13px; height: 13px; }

        .summary-line {
            display: flex; align-items: flex-start; gap: 12px;
            background: var(--grad-soft);
            border: 1px solid var(--green-line);
            border-radius: 16px;
            padding: 14px 16px;
            font-size: 15px; line-height: 1.55; color: var(--slate);
            font-weight: 500;
            animation: msgIn .45s ease both;
        }
        .summary-line .s-ico { font-size: 20px; line-height: 1; }

        .new-search {
            display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap;
            border-top: 1px dashed var(--border-strong);
            margin-top: 6px; padding-top: 18px;
            animation: msgIn .45s ease both;
        }
        .new-search .ns-text { font-size: 15px; color: var(--text-soft); }
        .new-search .ns-btn {
            display: inline-flex; align-items: center; gap: 8px;
            border: none; background: var(--grad); color: #fff;
            font: inherit; font-size: 14px; font-weight: 500;
            padding: 10px 18px; border-radius: 999px; cursor: pointer;
            box-shadow: var(--shadow-sm); transition: transform .15s, box-shadow .15s;
        }
        .new-search .ns-btn:hover { transform: translateY(-1px); box-shadow: var(--shadow-md); }

        /* ---------- Composer ---------- */
        .composer-wrap {
            flex: 0 0 auto;
            padding: 6px 18px calc(14px + env(safe-area-inset-bottom));
            background: linear-gradient(to top, var(--page) 62%, rgba(241,246,243,0));
            z-index: 20;
        }
        .composer {
            max-width: var(--maxw);
            margin: 0 auto;
            position: relative;
        }

        .attach-row {
            display: none;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 8px;
        }
        .attach-row.show { display: flex; }
        .attach-chip {
            display: flex; align-items: center; gap: 8px;
            background: var(--green-soft);
            border: 1px solid var(--green-line);
            border-radius: 14px;
            padding: 6px 8px;
            animation: msgIn .22s ease both;
        }
        .attach-chip .ap-icon { color: var(--green-dark); display: inline-flex; }
        .attach-chip audio { height: 34px; max-width: 320px; min-width: 0; }
        .attach-chip img.thumb-img { width: 46px; height: 46px; border-radius: 10px; object-fit: cover; }
        .attach-chip .ap-x {
            border: none; background: transparent; color: var(--text-soft);
            cursor: pointer; font-size: 18px; line-height: 1; padding: 4px 6px; border-radius: 8px;
        }
        .attach-chip .ap-x:hover { background: rgba(0,0,0,.06); color: var(--text); }

        .pill {
            display: flex;
            align-items: center;   /* keep +/mic/send vertically centered as the textarea grows */
            gap: 4px;
            background: var(--input-bg);
            border: 1px solid transparent;
            border-radius: 28px;
            padding: 6px 7px;
            box-shadow: var(--shadow-sm);
            transition: background .18s, box-shadow .18s, border-color .18s;
        }
        .pill:focus-within { background: var(--surface); border-color: var(--green-line); box-shadow: var(--shadow-md); }

        .pill textarea {
            flex: 1;
            min-width: 0;        /* let it shrink instead of overflowing the row */
            border: none; outline: none; resize: none;
            background: transparent;
            font: inherit; font-size: 16px; line-height: 1.5;  /* >=16px: no focus-zoom on iOS */
            color: var(--text);
            padding: 9px 6px;
            max-height: 168px;
            min-height: 24px;
            overflow-y: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        .pill textarea::-webkit-scrollbar { width: 0; height: 0; display: none; }
        .pill textarea::placeholder { color: var(--text-faint); }

        .icon-btn {
            flex: 0 0 auto;
            width: 42px; height: 42px;
            border: none; border-radius: 50%;
            background: transparent;
            color: var(--text-soft);
            cursor: pointer;
            display: grid; place-items: center;
            transition: background .15s, color .15s, transform .12s, box-shadow .15s;
        }
        .icon-btn:hover:not(:disabled) { background: rgba(19,160,106,.12); color: var(--green-dark); }
        .icon-btn:active:not(:disabled) { transform: scale(.92); }
        .icon-btn svg { width: 22px; height: 22px; }
        .icon-btn:disabled { opacity: .42; cursor: not-allowed; }

        .attach-wrap { position: relative; flex: 0 0 auto; align-self: center; }
        .attach-btn svg { width: 24px; height: 24px; }

        .attach-menu {
            position: absolute;
            bottom: calc(100% + 10px);
            left: 0;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: var(--shadow-lg);
            padding: 6px;
            display: none;
            flex-direction: column;
            min-width: 188px;
            z-index: 45;
        }
        .attach-menu.show { display: flex; animation: menuPop .16s ease both; }
        @keyframes menuPop { from { opacity: 0; transform: translateY(6px) scale(.97); } to { opacity: 1; transform: none; } }
        @keyframes sheetUp { from { transform: translateY(100%); } to { transform: none; } }
        .attach-menu button {
            display: flex; align-items: center; gap: 12px;
            border: none; background: transparent;
            font: inherit; font-size: 14.5px; color: var(--text);
            padding: 11px 12px; border-radius: 11px; cursor: pointer; text-align: left;
        }
        .attach-menu button:hover { background: var(--green-soft-2); }
        .attach-menu button .mi { font-size: 18px; width: 22px; text-align: center; }

        .mic-btn.recording {
            background: #fce8e6 !important;
            color: #d93025 !important;
            animation: recPulse 1.25s ease-in-out infinite;
        }
        @keyframes recPulse {
            0%,100% { box-shadow: 0 0 0 0 rgba(217,48,37,.40); }
            50% { box-shadow: 0 0 0 8px rgba(217,48,37,0); }
        }

        .send-btn { background: var(--green); color: #fff; }
        .send-btn:hover:not(:disabled) { background: var(--green-dark); color: #fff; box-shadow: var(--shadow-sm); }
        .send-btn:disabled { background: #bcd4c8; color: #fff; opacity: 1; }

        .listening-hint {
            display: none;
            text-align: center;
            font-size: 13px; color: #d93025;
            margin-top: 8px; font-weight: 500;
        }
        .listening-hint.show { display: block; animation: fadeIn .3s ease both; }
        .listening-hint .blip {
            display: inline-block; width: 8px; height: 8px; border-radius: 50%;
            background: #d93025; margin-right: 6px;
            animation: bounce 1.2s infinite ease-in-out; vertical-align: middle;
        }

        .composer-foot { text-align: center; font-size: 11.5px; color: var(--text-faint); margin-top: 9px; }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        /* ---------- Toast ---------- */
        .toast {
            position: fixed; left: 50%; bottom: 110px; transform: translateX(-50%) translateY(12px);
            background: var(--slate); color: #fff; font-size: 13.5px;
            padding: 11px 18px; border-radius: 12px; box-shadow: var(--shadow-lg);
            opacity: 0; pointer-events: none; transition: opacity .25s, transform .25s; z-index: 80;
            max-width: 90vw;
        }
        .toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }

        /* ---------- Map modal ---------- */
        .modal-backdrop {
            position: fixed; inset: 0;
            background: rgba(28,51,68,.45);
            backdrop-filter: blur(2px);
            display: none;
            align-items: center; justify-content: center;
            padding: 18px;
            z-index: 70;
        }
        .modal-backdrop.show { display: flex; animation: fadeIn .2s ease both; }
        .modal {
            background: var(--surface);
            border-radius: 20px;
            width: 100%;
            max-width: 480px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            display: flex; flex-direction: column;
            animation: modalIn .24s cubic-bezier(.22,.61,.36,1) both;
        }
        @keyframes modalIn { from { opacity: 0; transform: translateY(14px) scale(.98); } to { opacity: 1; transform: none; } }
        .modal-head {
            display: flex; align-items: center; justify-content: space-between;
            padding: 14px 16px;
            border-bottom: 1px solid var(--border);
        }
        .modal-head .mh-title { display: flex; align-items: center; gap: 9px; font-size: 16px; font-weight: 600; color: var(--slate); }
        .modal-head .mh-title .mh-dot { font-size: 18px; }
        .modal-head .mh-close {
            border: none; background: transparent; color: var(--text-soft);
            font-size: 22px; line-height: 1; cursor: pointer; padding: 4px 8px; border-radius: 10px;
        }
        .modal-head .mh-close:hover { background: var(--green-soft-2); color: var(--text); }
        #leafletMap { width: 100%; height: 320px; background: var(--green-soft-2); }
        .modal-controls { padding: 14px 16px 6px; }
        .modal-controls .rc-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
        .modal-controls .rc-label { font-size: 14px; color: var(--text-soft); font-weight: 500; }
        .modal-controls .rc-val { font-size: 14px; font-weight: 600; color: var(--green-dark); background: var(--green-soft); padding: 3px 10px; border-radius: 999px; }
        .modal-controls input[type=range] {
            width: 100%; accent-color: var(--green); height: 6px; cursor: pointer;
        }
        .modal-foot { display: flex; gap: 10px; padding: 12px 16px 16px; }
        .modal-foot button { flex: 1; font: inherit; font-size: 14.5px; font-weight: 500; padding: 11px 16px; border-radius: 12px; cursor: pointer; transition: background .15s, box-shadow .15s; }
        .modal-foot .btn-cancel { border: 1px solid var(--border-strong); background: var(--surface); color: var(--text); }
        .modal-foot .btn-cancel:hover { background: var(--green-soft-2); }
        .modal-foot .btn-confirm { border: none; background: var(--green); color: #fff; box-shadow: var(--shadow-sm); }
        .modal-foot .btn-confirm:hover { background: var(--green-dark); }

        /* ---------- Responsive ---------- */
        @media (max-width: 760px) {
            .column { padding: 14px 16px 26px; }
        }

        @media (max-width: 600px) {
            /* Top bar: compact, single row, no wrap/overflow */
            .topbar { padding: 10px 12px; gap: 8px; flex-wrap: nowrap; }
            .wordmark { font-size: 17px; gap: 8px; min-width: 0; }
            .wordmark .brand-dot { width: 22px; height: 22px; }
            .wordmark .brand { white-space: nowrap; }
            .lang-switch button { padding: 5px 9px; }
            .newchat-btn .label { display: none; }
            .newchat-btn { padding: 8px; width: 40px; height: 40px; justify-content: center; flex: 0 0 auto; }

            .column { padding: 12px 14px 24px; }
            .suggestions { grid-template-columns: 1fr; }
            .results { grid-template-columns: 1fr; }
            .composer-wrap { padding: 4px 12px calc(12px + env(safe-area-inset-bottom)); }

            /* Bubbles + media */
            .msg { gap: 10px; }
            .msg.user .bubble { max-width: 88%; }
            .voice-attach { max-width: 88%; }
            .voice-attach audio { width: 100%; max-width: 100%; }
            .user-media img { max-width: min(240px, 72vw); }

            /* Guided cards: full width, larger touch targets (>=40px) */
            .gcard { max-width: 100%; }
            .gchip { padding: 11px 16px; font-size: 15px; }
            .gstep-btn { width: 40px; height: 40px; font-size: 21px; }
            .gstep-val { min-width: 34px; font-size: 17px; }
            .gswitch { width: 50px; height: 30px; }
            .gknob { width: 24px; height: 24px; }
            .gswitch.on .gknob { left: 23px; }
            .gbtn { padding: 13px 18px; font-size: 15px; }
            .greeting.compact h1 { font-size: clamp(24px, 7vw, 32px); }
            .greeting.compact h2 { font-size: clamp(18px, 5.5vw, 24px); }

            /* Attach "+" menu → bottom sheet */
            .attach-menu {
                position: fixed; left: 0; right: 0; bottom: 0; top: auto;
                width: 100%; min-width: 0;
                border-radius: 18px 18px 0 0;
                padding: 8px 10px calc(10px + env(safe-area-inset-bottom));
                box-shadow: 0 -8px 28px rgba(28,51,68,.20);
                z-index: 75;
            }
            .attach-menu.show { animation: sheetUp .2s ease both; }
            .attach-menu button { padding: 15px 14px; font-size: 16px; border-radius: 13px; }

            /* Map modal → bottom sheet with safe-area inset */
            .modal-backdrop { padding: 0; align-items: flex-end; }
            .modal { max-width: 100%; max-height: 92dvh; border-radius: 20px 20px 0 0; }
            .modal-foot { padding: 12px 14px calc(14px + env(safe-area-inset-bottom)); }
            #leafletMap { height: clamp(220px, 42dvh, 340px); }
            .toast { bottom: calc(96px + env(safe-area-inset-bottom)); }
        }

        @media (max-width: 380px) {
            .wordmark .brand { font-size: 16px; }
            .lang-switch button { padding: 5px 7px; font-size: 12px; }
            .column { padding: 10px 12px 22px; }
            .gchip { padding: 10px 13px; }
            .icon-btn { width: 40px; height: 40px; }
        }

        /* Touch devices: drop sticky hover lifts, keep tap feedback */
        @media (hover: none) {
            .card:hover, .suggest-card:hover, .new-search .ns-btn:hover,
            .gbtn-primary:hover, .gchip:hover, .chip:hover, .icon-btn:hover { transform: none; }
            .card:hover, .suggest-card:hover { box-shadow: var(--shadow-sm); }
        }
    </style>
</head>
<body>
    <!-- ===== Top bar ===== -->
    <header class="topbar" id="topbar">
        <div class="wordmark">
            <span class="brand-dot" aria-hidden="true"></span>
            <span class="brand">boshpana.ai</span>
        </div>
        <div class="spacer"></div>
        <div class="lang-switch" id="langSwitch" role="tablist" aria-label="Language">
            <button data-lang="uz" class="active">uz</button>
            <button data-lang="ru">ru</button>
            <button data-lang="en">en</button>
        </div>
        <button class="newchat-btn" id="newChatBtn" title="">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
            <span class="label" data-i18n="newChat">Yangi suhbat</span>
        </button>
    </header>

    <!-- ===== Conversation ===== -->
    <main class="scroll" id="scroll">
        <div class="column" id="column">
            <section class="greeting" id="greeting">
                <h1 id="greetTitle">Salom 👋</h1>
                <h2 id="greetSub">Qanday kvartira topib beray?</h2>
                <div class="suggestions" id="suggestions"></div>
            </section>
            <section class="messages" id="messages" aria-live="polite"></section>
        </div>
    </main>

    <!-- ===== Composer ===== -->
    <div class="composer-wrap">
        <div class="composer">
            <div class="attach-row" id="attachRow">
                <div class="attach-chip" id="imageChip" style="display:none">
                    <img class="thumb-img" id="imageThumb" alt="">
                    <button class="ap-x" id="imageX" title="" aria-label="remove image">&times;</button>
                </div>
                <div class="attach-chip" id="audioChip" style="display:none">
                    <span class="ap-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a3 3 0 0 0-3 3v6a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/><path d="M19 10v1a7 7 0 0 1-14 0v-1M12 18v4"/></svg>
                    </span>
                    <audio id="attachAudio" controls></audio>
                    <button class="ap-x" id="audioX" title="" aria-label="remove audio">&times;</button>
                </div>
            </div>
            <div class="pill">
                <div class="attach-wrap">
                    <button class="icon-btn attach-btn" id="attachBtn" title="" aria-label="attach" aria-haspopup="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                    </button>
                    <div class="attach-menu" id="attachMenu" role="menu">
                        <button id="menuPhoto" role="menuitem"><span class="mi">📷</span><span class="ml" data-i18n="menuPhoto">Rasm</span></button>
                        <button id="menuLocation" role="menuitem"><span class="mi">📍</span><span class="ml" data-i18n="menuLocation">Lokatsiya</span></button>
                    </div>
                </div>
                <textarea id="input" rows="1" placeholder="" autocomplete="off" spellcheck="false"></textarea>
                <button class="icon-btn mic-btn" id="micBtn" title="" aria-label="microphone">
                    <svg class="mic-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a3 3 0 0 0-3 3v6a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/><path d="M19 10v1a7 7 0 0 1-14 0v-1M12 18v4M8 22h8"/></svg>
                </button>
                <button class="icon-btn send-btn" id="sendBtn" disabled title="" aria-label="send">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
                </button>
            </div>
            <div class="listening-hint" id="listeningHint"><span class="blip"></span><span id="listeningText">Tinglayapman...</span></div>
            <div class="composer-foot" id="composerFoot"></div>
        </div>
    </div>

    <input type="file" id="imageInput" accept="image/*" style="display:none">

    <!-- ===== Map modal ===== -->
    <div class="modal-backdrop" id="mapModal">
        <div class="modal" role="dialog" aria-modal="true">
            <div class="modal-head">
                <span class="mh-title"><span class="mh-dot">📍</span><span id="mapTitle">Lokatsiyani tanlang</span></span>
                <button class="mh-close" id="mapClose" aria-label="close">&times;</button>
            </div>
            <div id="leafletMap"></div>
            <div class="modal-controls">
                <div class="rc-row">
                    <span class="rc-label" id="radiusLabel">Qidiruv radiusi</span>
                    <span class="rc-val" id="radiusVal">2 km</span>
                </div>
                <input type="range" id="radiusSlider" min="0.5" max="10" step="0.5" value="2">
            </div>
            <div class="modal-foot">
                <button class="btn-cancel" id="mapCancel">Bekor qilish</button>
                <button class="btn-confirm" id="mapConfirm">Tasdiqlash</button>
            </div>
        </div>
    </div>

    <div class="toast" id="toast"></div>

    <script>
    (function () {
        "use strict";

        // ---------- i18n ----------
        var I18N = {
            uz: {
                html: "uz",
                greetTitle: "Salom 👋",
                greetSub: "Qanday kvartira topib beray?",
                placeholder: "Masalan: Toshkentdan 300$ gacha, 2 xonali, metroga yaqin kvartira kerak…",
                newChat: "Yangi suhbat",
                newChatTip: "Suhbatni tozalash va boshidan boshlash",
                send: "Yuborish",
                attachTip: "Qo‘shish",
                menuPhoto: "Rasm",
                menuLocation: "Lokatsiya",
                micTip: "Ovozli xabar — bosib gapiring",
                micStopTip: "To‘xtatish",
                micDeniedTip: "Mikrofonga ruxsat berilmadi",
                listening: "Tinglayapman...",
                voiceNote: "🎤 Ovozli xabar",
                voiceLabel: "Ovozli xabar",
                imageNote: "🖼️ Rasm",
                view: "Ko‘rish",
                rooms: "xona",
                match: "moslik",
                newSearchText: "Yangi qidiruv boshlaymizmi?",
                newSearchBtn: "Yangi qidiruv",
                sendError: "Xatolik yuz berdi. Iltimos, qayta urinib ko‘ring.",
                speechUnsupported: "Ovozni matnga aylantirish bu brauzerda ishlamaydi — ovozli xabar yuborildi.",
                removeAttach: "O‘chirish",
                foot: "Boshpana.ai xatoga yo‘l qo‘yishi mumkin — ma’lumotlarni tekshiring.",
                metroYes: "metroga yaqin",
                searchingLine: "Uy egalari bilan Telegram orqali bog‘lanmoqdaman…",
                searchingCounts: "📨 {c} ta uy egasi · ✅ {a} rozi",
                stages: { searching: "Qidirilmoqda", checking: "E’lonlar tekshirilmoqda", contacting: "Uy egalariga yozilmoqda", waiting: "Javoblar kutilmoqda", done: "Tayyor" },
                noResults: "Afsus, bu safar hech bir uy egasi rozi bo‘lmadi. Boshqa shartlar bilan urinib ko‘ring.",
                pollTimeout: "Qidiruv hali davom etmoqda — birozdan keyin qaytib qarang.",
                mapTitle: "Lokatsiyani tanlang",
                radiusLabel: "Qidiruv radiusi",
                mapConfirm: "Tasdiqlash",
                mapCancel: "Bekor qilish",
                mapError: "Xaritani yuklab bo‘lmadi. Internetni tekshiring.",
                km: "km",
                locationMsg: "📍 Tanlangan joy atrofida qidiraman (radius ~{R} km).",
                suggestions: [
                    { t: "Chilonzorda 2 xonali, 500$ gacha", i: "🏙️" },
                    { t: "Sherikchilik, metroga yaqin", i: "🤝" },
                    { t: "Qizlar uchun, 1 xona", i: "👩" },
                    { t: "Mebelli, komissiyasiz", i: "🛋️" }
                ]
            },
            ru: {
                html: "ru",
                greetTitle: "Привет 👋",
                greetSub: "Какую квартиру вам найти?",
                placeholder: "Например: ищу квартиру в Ташкенте до 300$, 2 комнаты, рядом метро…",
                newChat: "Новый чат",
                newChatTip: "Очистить разговор и начать заново",
                send: "Отправить",
                attachTip: "Добавить",
                menuPhoto: "Фото",
                menuLocation: "Локация",
                micTip: "Голосовое сообщение — нажмите и говорите",
                micStopTip: "Остановить",
                micDeniedTip: "Доступ к микрофону запрещён",
                listening: "Слушаю...",
                voiceNote: "🎤 Голосовое сообщение",
                voiceLabel: "Голосовое сообщение",
                imageNote: "🖼️ Фото",
                view: "Открыть",
                rooms: "комн.",
                match: "совпадение",
                newSearchText: "Начнём новый поиск?",
                newSearchBtn: "Новый поиск",
                sendError: "Произошла ошибка. Пожалуйста, попробуйте ещё раз.",
                speechUnsupported: "Распознавание речи не работает в этом браузере — голосовое сообщение отправлено.",
                removeAttach: "Удалить",
                foot: "Boshpana.ai может ошибаться — проверяйте информацию.",
                metroYes: "рядом метро",
                searchingLine: "Связываюсь с владельцами в Telegram…",
                searchingCounts: "📨 {c} владельцев · ✅ {a} согласились",
                stages: { searching: "Идёт поиск", checking: "Проверяю объявления", contacting: "Пишу владельцам", waiting: "Жду ответы", done: "Готово" },
                noResults: "К сожалению, в этот раз никто из владельцев не согласился. Попробуйте с другими условиями.",
                pollTimeout: "Поиск ещё идёт — загляните чуть позже.",
                mapTitle: "Выберите локацию",
                radiusLabel: "Радиус поиска",
                mapConfirm: "Подтвердить",
                mapCancel: "Отмена",
                mapError: "Не удалось загрузить карту. Проверьте интернет.",
                km: "км",
                locationMsg: "📍 Буду искать рядом с выбранным местом (радиус ~{R} км).",
                suggestions: [
                    { t: "Чиланзар, 2 комнаты, до 500$", i: "🏙️" },
                    { t: "Подселение, рядом метро", i: "🤝" },
                    { t: "Для девушек, 1 комната", i: "👩" },
                    { t: "С мебелью, без комиссии", i: "🛋️" }
                ]
            },
            en: {
                html: "en",
                greetTitle: "Hello 👋",
                greetSub: "What apartment can I find for you?",
                placeholder: "e.g. I need a 2-room flat in Tashkent up to $300, near metro…",
                newChat: "New chat",
                newChatTip: "Clear the conversation and start over",
                send: "Send",
                attachTip: "Add",
                menuPhoto: "Photo",
                menuLocation: "Location",
                micTip: "Voice message — press and speak",
                micStopTip: "Stop",
                micDeniedTip: "Microphone access denied",
                listening: "Listening...",
                voiceNote: "🎤 Voice message",
                voiceLabel: "Voice message",
                imageNote: "🖼️ Photo",
                view: "View",
                rooms: "rooms",
                match: "match",
                newSearchText: "Start a new search?",
                newSearchBtn: "New search",
                sendError: "Something went wrong. Please try again.",
                speechUnsupported: "Speech-to-text is not available in this browser — voice message sent.",
                removeAttach: "Remove",
                foot: "Boshpana.ai can make mistakes — please verify the details.",
                metroYes: "near metro",
                searchingLine: "Contacting owners on Telegram…",
                searchingCounts: "📨 {c} owners · ✅ {a} agreed",
                stages: { searching: "Searching", checking: "Checking listings", contacting: "Talking to owners", waiting: "Waiting for replies", done: "Done" },
                noResults: "Sorry, no owner agreed this time. Try with different conditions.",
                pollTimeout: "The search is still running — check back in a little while.",
                mapTitle: "Choose location",
                radiusLabel: "Search radius",
                mapConfirm: "Confirm",
                mapCancel: "Cancel",
                mapError: "Could not load the map. Check your connection.",
                km: "km",
                locationMsg: "📍 I'll search around the selected location (radius ~{R} km).",
                suggestions: [
                    { t: "Chilonzor, 2 rooms, up to $500", i: "🏙️" },
                    { t: "Shared rent, near metro", i: "🤝" },
                    { t: "For girls, 1 room", i: "👩" },
                    { t: "Furnished, no commission", i: "🛋️" }
                ]
            }
        };
        var SPEECH_LANG = { uz: "uz-UZ", ru: "ru-RU", en: "en-US" };
        var TASHKENT = [41.2995, 69.2401];

        // Localized natural-language sentences composed when a card is submitted.
        var TPL = {
            uz: {
                region: "{label}dan qidiryapman.",
                budget: "Byudjetim {label}.",
                household: "Uyda {n} kishi yashaydi.",
                household_furnished: " Mebelli bo‘lsa yaxshi.",
                rooms: "Menga {n} xonali kerak.",
                rooms_any: "Xonalar soni farqi yo‘q.",
                musthaves: "Talablarim: {list}.",
                musthaves_empty: "Maxsus talab yo‘q."
            },
            ru: {
                region: "Ищу в {label}.",
                budget: "Мой бюджет {label}.",
                household: "В квартире будет проживать {n} чел.",
                household_furnished: " Желательно с мебелью.",
                rooms: "Мне нужно {n} комн.",
                rooms_any: "Количество комнат не важно.",
                musthaves: "Обязательно: {list}.",
                musthaves_empty: "Без особых условий."
            },
            en: {
                region: "I'm looking in {label}.",
                budget: "My budget is {label}.",
                household: "My household has {n} occupant(s).",
                household_furnished: " I'd prefer it furnished.",
                rooms: "I need {n} room(s).",
                rooms_any: "Number of rooms doesn't matter.",
                musthaves: "Must-haves: {list}.",
                musthaves_empty: "No specific must-haves."
            }
        };
        var ICON_EMOJI = { "map-pin": "📍", wallet: "💰", user: "👤", door: "🚪", sparkles: "✨" };

        // ---------- State ----------
        var lang = "uz";
        var activeCardRow = null;
        var pendingAudioUrl = null;
        var pendingImageUrl = null;
        var isRecording = false;
        var isSending = false;
        var recognition = null;
        var mediaRecorder = null;
        var audioChunks = [];
        var micStream = null;
        var speechBase = "";
        var micDenied = false;

        var SR = window.SpeechRecognition || window.webkitSpeechRecognition || null;
        var speechSupported = !!SR;
        var micSupported = !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia && window.MediaRecorder);

        // ---------- DOM ----------
        var token = document.querySelector('meta[name=csrf-token]').content;
        var $ = function (id) { return document.getElementById(id); };
        var topbar = $("topbar"), scroll = $("scroll"), greeting = $("greeting"),
            messages = $("messages"), input = $("input"), sendBtn = $("sendBtn"),
            micBtn = $("micBtn"), suggestions = $("suggestions"), langSwitch = $("langSwitch"),
            newChatBtn = $("newChatBtn"), attachRow = $("attachRow"),
            imageChip = $("imageChip"), imageThumb = $("imageThumb"), imageX = $("imageX"),
            audioChip = $("audioChip"), attachAudio = $("attachAudio"), audioX = $("audioX"),
            listeningHint = $("listeningHint"), listeningText = $("listeningText"),
            composerFoot = $("composerFoot"), toastEl = $("toast"),
            greetTitle = $("greetTitle"), greetSub = $("greetSub"),
            attachBtn = $("attachBtn"), attachMenu = $("attachMenu"),
            menuPhoto = $("menuPhoto"), menuLocation = $("menuLocation"), imageInput = $("imageInput"),
            mapModal = $("mapModal"), mapClose = $("mapClose"), mapCancel = $("mapCancel"),
            mapConfirm = $("mapConfirm"), radiusSlider = $("radiusSlider"), radiusVal = $("radiusVal"),
            mapTitleEl = $("mapTitle"), radiusLabelEl = $("radiusLabel");

        function t(k) { return I18N[lang][k]; }

        // ---------- Helpers ----------
        function el(tag, cls, html) {
            var e = document.createElement(tag);
            if (cls) e.className = cls;
            if (html != null) e.innerHTML = html;
            return e;
        }
        function esc(s) {
            return String(s == null ? "" : s)
                .replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;");
        }
        function fmtKm(r) {
            var n = (r % 1 === 0) ? r.toString() : r.toString();
            return n + " " + t("km");
        }
        var scrollPinned = true;
        scroll.addEventListener("scroll", function () {
            scrollPinned = scroll.scrollHeight - scroll.scrollTop - scroll.clientHeight < 80;
            topbar.classList.toggle("scrolled", scroll.scrollTop > 4);
        });
        function scrollToBottom(force) {
            if (force || scrollPinned) {
                requestAnimationFrame(function () { scroll.scrollTop = scroll.scrollHeight; });
            }
        }
        var toastTimer;
        function toast(msg) {
            toastEl.textContent = msg;
            toastEl.classList.add("show");
            clearTimeout(toastTimer);
            toastTimer = setTimeout(function () { toastEl.classList.remove("show"); }, 3600);
        }

        var AVATAR_SVG = '<svg viewBox="0 0 24 24" fill="#fff"><path d="M12 2c.5 4.6 2.4 6.5 7 7-4.6.5-6.5 2.4-7 7-.5-4.6-2.4-6.5-7-7 4.6-.5 6.5-2.4 7-7Z"/></svg>';

        // ---------- Static localization ----------
        function applyStatic() {
            var d = I18N[lang];
            document.documentElement.lang = d.html;
            greetTitle.textContent = d.greetTitle;
            greetSub.textContent = d.greetSub;
            input.placeholder = d.placeholder;
            composerFoot.textContent = d.foot;
            listeningText.textContent = d.listening;
            newChatBtn.title = d.newChatTip;
            newChatBtn.querySelector(".label").textContent = d.newChat;
            sendBtn.title = d.send;
            attachBtn.title = d.attachTip;
            imageX.title = d.removeAttach;
            audioX.title = d.removeAttach;
            menuPhoto.querySelector(".ml").textContent = d.menuPhoto;
            menuLocation.querySelector(".ml").textContent = d.menuLocation;
            micBtn.title = micDenied ? d.micDeniedTip : (isRecording ? d.micStopTip : d.micTip);
            mapTitleEl.textContent = d.mapTitle;
            radiusLabelEl.textContent = d.radiusLabel;
            mapConfirm.textContent = d.mapConfirm;
            mapCancel.textContent = d.mapCancel;
            radiusVal.textContent = fmtKm(parseFloat(radiusSlider.value));
            renderSuggestions();
        }

        function renderSuggestions() {
            suggestions.innerHTML = "";
            I18N[lang].suggestions.forEach(function (s) {
                var b = el("button", "suggest-card");
                b.innerHTML = '<span class="txt">' + esc(s.t) + '</span><span class="ico">' + s.i + '</span>';
                b.addEventListener("click", function () { send(s.t, null); });
                suggestions.appendChild(b);
            });
        }

        // ---------- Rendering messages ----------
        function hideGreeting() {
            if (greeting.style.display !== "none") greeting.style.display = "none";
        }

        function uavatar() {
            return '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21a8 8 0 1 0-16 0"/><circle cx="12" cy="8" r="4"/></svg>';
        }

        function addUserMessage(text, opts) {
            opts = opts || {};
            var realText = text && text !== t("voiceNote") && text !== t("imageNote");
            var row = el("div", "msg user");
            row.innerHTML = '<div class="avatar">' + uavatar() + '</div><div class="body"></div>';
            var body = row.querySelector(".body");

            if (opts.audioUrl) {
                var va = el("div", "voice-attach");
                va.innerHTML = '<div class="voice-head">' +
                    '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a3 3 0 0 0-3 3v6a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/><path d="M19 10v1a7 7 0 0 1-14 0v-1M12 18v4"/></svg>' +
                    '<span>' + esc(t("voiceLabel")) + '</span></div>';
                var au = el("audio"); au.controls = true; au.src = opts.audioUrl;
                va.appendChild(au);
                if (realText) va.appendChild(el("div", "vtext", esc(text)));
                body.appendChild(va);
            } else {
                if (opts.imageUrl) {
                    var media = el("div", "user-media");
                    var img = el("img"); img.src = opts.imageUrl; img.alt = "";
                    media.appendChild(img);
                    body.appendChild(media);
                }
                if (realText) body.appendChild(el("div", "bubble", esc(text)));
                if (opts.locationLine) body.appendChild(el("div", "loc-meta", esc(opts.locationLine)));
            }
            messages.appendChild(row);
            scrollToBottom(true);
        }

        function showTyping() {
            var row = el("div", "msg ai typing");
            row.innerHTML = '<div class="avatar pulsing">' + AVATAR_SVG + '</div>' +
                '<div class="body"><div class="dots"><span></span><span></span><span></span></div></div>';
            messages.appendChild(row);
            scrollToBottom(true);
            return row;
        }

        function addAIMessage(text) {
            var row = el("div", "msg ai");
            row.innerHTML = '<div class="avatar">' + AVATAR_SVG + '</div><div class="body"></div>';
            row.querySelector(".body").appendChild(el("div", "bubble", esc(text)));
            messages.appendChild(row);
            scrollToBottom(true);
            return row;
        }

        function renderChips(options, aiRow) {
            if (!options || !options.length) return;
            var wrap = el("div", "chips");
            options.forEach(function (o) {
                var b = el("button", "chip", esc(o.label));
                b.addEventListener("click", function () {
                    wrap.remove();
                    send(o.value, null);
                });
                wrap.appendChild(b);
            });
            aiRow.querySelector(".body").appendChild(wrap);
            scrollToBottom(true);
        }

        function metroOn(v) {
            if (v == null) return false;
            if (typeof v === "boolean") return v;
            var s = String(v).toLowerCase();
            return !(s === "" || s === "any" || s === "no" || s === "0" || s === "false");
        }

        function renderResults(results) {
            var grid = el("div", "results");
            results.forEach(function (r) {
                var card = el("div", "card");
                var img = (r.images && r.images.length && r.images[0]) ? r.images[0] : null;
                var thumb = '<div class="thumb"' + (img ? ' style="background-image:url(\'' + esc(img) + '\')"' : '') + '>';
                if (!img) thumb += '<span class="ph">🏠</span>';
                if (r.score != null) thumb += '<span class="score-badge">✦ ' + esc(r.score) + '% ' + esc(t("match")) + '</span>';
                var priceTxt = r.price != null ? (r.currency && r.currency !== "USD" ? esc(r.price) + " " + esc(r.currency) : "$" + esc(r.price)) : "—";
                thumb += '<span class="price-badge">' + priceTxt + '</span></div>';

                var metaParts = [];
                if (r.rooms != null) metaParts.push("🚪 " + esc(r.rooms) + " " + esc(t("rooms")));
                if (r.area != null) metaParts.push("📐 " + esc(r.area) + " m²");
                if (r.district) metaParts.push("📍 " + esc(r.district));
                if (metroOn(r.near_metro)) metaParts.push("🚇 " + esc(t("metroYes")));

                var info = '<div class="info">';
                info += '<div class="title">' + esc(r.title || "—") + '</div>';
                info += '<div class="meta">' + metaParts.join(" · ") + '</div>';
                info += '<div class="foot">';
                info += '<span class="source-pill">' + esc(r.source || "—") + '</span>';
                if (r.url) {
                    info += '<a class="view-btn" href="' + esc(r.url) + '" target="_blank" rel="noopener noreferrer">' +
                        esc(t("view")) + ' <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17 17 7M9 7h8v8"/></svg></a>';
                }
                info += '</div></div>';
                card.innerHTML = thumb + info;
                grid.appendChild(card);
            });
            messages.appendChild(grid);
            scrollToBottom(true);
        }

        function renderSummary(text) {
            var s = el("div", "summary-line");
            s.innerHTML = '<span class="s-ico">🎉</span><span>' + esc(text) + '</span>';
            messages.appendChild(s);
            scrollToBottom(true);
        }

        function renderNewSearch() {
            var n = el("div", "new-search");
            n.innerHTML = '<span class="ns-text">' + esc(t("newSearchText")) + '</span>';
            var b = el("button", "ns-btn");
            b.innerHTML = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7M21 3v6h-6"/></svg> ' + esc(t("newSearchBtn"));
            b.addEventListener("click", function () { resetChat(); });
            n.appendChild(b);
            messages.appendChild(n);
            scrollToBottom(true);
        }

        // ---------- Searching state + polling (real async search) ----------
        var POLL_INTERVAL = 3000;
        var POLL_MAX_MS = 180000; // ~3 min safety cap
        var pollTimer = null;
        var pollDeadline = 0;
        var searchingRow = null;
        var STAGES = ["searching", "checking", "contacting", "waiting", "done"];
        var STAGE_TICK = 1100;   // ms per auto-advanced early stage
        var timeIndex = 0;       // auto-advances 0 -> 1 -> 2 (caps at "contacting")
        var backendIndex = 0;    // index of the `stage` reported by /chat/status
        var stageTimer = null;

        function showSearching() {
            var row = el("div", "msg ai");
            var rowsHtml = "";
            STAGES.forEach(function (s) {
                rowsHtml += '<div class="cl-row upcoming"><span class="cl-mark"><span class="cl-circle"></span></span>' +
                    '<span class="cl-label">' + esc(t("stages")[s]) + '</span></div>';
            });
            row.innerHTML = '<div class="avatar pulsing">' + AVATAR_SVG + '</div>' +
                '<div class="body">' +
                    '<div class="checklist">' + rowsHtml + '</div>' +
                    '<div class="s-progress" style="display:none"><div class="s-counts"></div></div>' +
                '</div>';
            messages.appendChild(row);
            scrollToBottom(true);
            return row;
        }

        function currentStageIdx() { return Math.max(timeIndex, backendIndex); }

        // Marker state per row: completed -> green check, current -> spinner, upcoming -> hollow circle.
        function renderChecklist(idx, allDone) {
            if (!searchingRow) return;
            var rows = searchingRow.querySelectorAll(".cl-row");
            for (var i = 0; i < rows.length; i++) {
                var mark = rows[i].querySelector(".cl-mark");
                rows[i].classList.remove("done", "current", "upcoming");
                if (allDone || i < idx) {
                    rows[i].classList.add("done");
                    mark.innerHTML = '<span class="cl-check">✓</span>';
                } else if (i === idx) {
                    rows[i].classList.add("current");
                    mark.innerHTML = '<span class="cl-spin"></span>';
                } else {
                    rows[i].classList.add("upcoming");
                    mark.innerHTML = '<span class="cl-circle"></span>';
                }
            }
        }

        function updateCounts(d) {
            if (!searchingRow) return;
            var c = d.contacted || 0, a = d.agreed || 0;
            if (c > 0) {
                searchingRow.querySelector(".s-progress").style.display = "block";
                searchingRow.querySelector(".s-counts").textContent =
                    t("searchingCounts").replace("{c}", c).replace("{a}", a);
            }
        }

        function updateSearching(d) {
            if (!searchingRow) return;
            if (d.stage) {
                var bi = STAGES.indexOf(d.stage);
                if (bi >= 0) backendIndex = bi;
            }
            renderChecklist(currentStageIdx(), false);
            updateCounts(d);
        }

        function stopStageTimer() {
            if (stageTimer) { clearTimeout(stageTimer); stageTimer = null; }
        }
        function scheduleStageTick() {
            stopStageTimer();
            if (timeIndex >= 2) return; // auto-advance caps at "contacting"
            stageTimer = setTimeout(function () {
                timeIndex = Math.min(2, timeIndex + 1);
                renderChecklist(currentStageIdx(), false);
                scheduleStageTick();
            }, STAGE_TICK);
        }

        function clearSearchingRow() {
            stopStageTimer();
            if (searchingRow) { searchingRow.remove(); searchingRow = null; }
        }
        function stopPolling() {
            if (pollTimer) { clearTimeout(pollTimer); pollTimer = null; }
        }

        function startSearch() {
            stopPolling();
            clearSearchingRow();
            timeIndex = 0;
            backendIndex = 0;
            searchingRow = showSearching();
            renderChecklist(0, false);
            scheduleStageTick();
            pollDeadline = Date.now() + POLL_MAX_MS;
            pollTimer = setTimeout(pollOnce, POLL_INTERVAL);
        }

        function schedulePoll() {
            if (Date.now() > pollDeadline) { onPollTimeout(); return; }
            pollTimer = setTimeout(pollOnce, POLL_INTERVAL);
        }

        function pollOnce() {
            fetch("/chat/status", {
                method: "GET",
                headers: { "Accept": "application/json" },
                credentials: "same-origin"
            }).then(function (r) {
                if (!r.ok) throw new Error("HTTP " + r.status);
                return r.json();
            }).then(function (d) {
                if (!searchingRow) return; // search was cancelled meanwhile
                if (d.done) {
                    stopPolling();
                    stopStageTimer();
                    backendIndex = STAGES.length - 1;
                    renderChecklist(STAGES.length, true); // every stage checked, incl. "Done"
                    updateCounts(d);
                    setTimeout(function () {
                        if (!searchingRow) return; // reset/new message during the brief pause
                        finishSearch(d);
                    }, 520);
                } else {
                    updateSearching(d);
                    schedulePoll();
                }
            }).catch(function () { schedulePoll(); });
        }

        function finishSearch(d) {
            clearSearchingRow();
            if (d.results && d.results.length) {
                renderResults(d.results);
                if (d.summary) renderSummary(d.summary);
            } else {
                addAIMessage(t("noResults"));
                if (d.summary) renderSummary(d.summary);
            }
            renderNewSearch();
        }

        function onPollTimeout() {
            stopPolling();
            clearSearchingRow();
            addAIMessage(t("pollTimeout"));
            renderNewSearch();
        }

        // ---------- Guided interactive cards ----------
        function fetchCard() {
            return fetch("/chat/card?lang=" + encodeURIComponent(lang), {
                method: "GET",
                headers: { "Accept": "application/json" },
                credentials: "same-origin"
            }).then(function (r) {
                if (!r.ok) throw new Error("HTTP " + r.status);
                return r.json();
            });
        }

        function bootstrapCards() {
            greeting.classList.add("compact");
            fetchCard().then(function (d) {
                var aiRow = addAIMessage(d.reply || "");
                if (d.card) renderCard(d.card, aiRow);
                if (d.status === "searching") startSearch();
            }).catch(function () {
                // Offline / no card: fall back to the suggestion greeting.
                greeting.classList.remove("compact");
            });
        }

        function composeCardMessage(field, value, label) {
            var tpl = TPL[lang];
            if (field === "region") return tpl.region.replace("{label}", label);
            if (field === "budget") return tpl.budget.replace("{label}", label);
            if (field === "rooms") {
                if (value === "any") return tpl.rooms_any;
                return tpl.rooms.replace("{n}", value);
            }
            if (field === "household") {
                var n = (value && value.occupants) || 1;
                var s = tpl.household.replace("{n}", n);
                if (value && value.furnished) s += tpl.household_furnished;
                return s;
            }
            return label || "";
        }
        function composeMustHaves(labels) {
            var tpl = TPL[lang];
            if (!labels.length) return tpl.musthaves_empty;
            return tpl.musthaves.replace("{list}", labels.join(", "));
        }

        function lockCard(panel) { panel.classList.add("locked"); }

        function submitCard(field, value, message) {
            activeCardRow = null;
            send(message, { field: field, value: value });
        }

        function buildSingleCard(card, panel, bodyWrap) {
            var chips = el("div", "gchips");
            card.choices.forEach(function (ch) {
                var b = el("button", "gchip");
                b.textContent = ch.label;
                b.addEventListener("click", function () {
                    if (panel.classList.contains("locked")) return;
                    lockCard(panel);
                    b.classList.add("selected");
                    submitCard(card.key, ch.value, composeCardMessage(card.key, ch.value, ch.label));
                });
                chips.appendChild(b);
            });
            bodyWrap.appendChild(chips);
        }

        function buildFormCard(card, panel, bodyWrap) {
            var state = {};
            card.fields.forEach(function (f) {
                var row = el("div", "gfield");
                row.innerHTML = '<div class="gfield-text"><div class="gfield-label">' + esc(f.label) + '</div>' +
                    (f.sublabel ? '<div class="gfield-sub">' + esc(f.sublabel) + '</div>' : '') + '</div>';
                if (f.type === "counter") {
                    var min = (f.min != null ? f.min : 1), max = (f.max != null ? f.max : 99);
                    state[f.key] = (f.value != null ? f.value : min);
                    var step = el("div", "gstepper");
                    var dec = el("button", "gstep-btn", "−");
                    var val = el("span", "gstep-val", String(state[f.key]));
                    var inc = el("button", "gstep-btn", "+");
                    var sync = function () { val.textContent = state[f.key]; dec.disabled = state[f.key] <= min; inc.disabled = state[f.key] >= max; };
                    dec.addEventListener("click", function () { if (state[f.key] > min) { state[f.key]--; sync(); } });
                    inc.addEventListener("click", function () { if (state[f.key] < max) { state[f.key]++; sync(); } });
                    step.appendChild(dec); step.appendChild(val); step.appendChild(inc);
                    row.appendChild(step);
                    sync();
                } else if (f.type === "toggle") {
                    state[f.key] = !!f.value;
                    var sw = el("button", "gswitch");
                    sw.setAttribute("role", "switch");
                    sw.setAttribute("aria-checked", state[f.key] ? "true" : "false");
                    sw.classList.toggle("on", state[f.key]);
                    sw.innerHTML = '<span class="gknob"></span>';
                    sw.addEventListener("click", function () {
                        state[f.key] = !state[f.key];
                        sw.classList.toggle("on", state[f.key]);
                        sw.setAttribute("aria-checked", state[f.key] ? "true" : "false");
                    });
                    row.appendChild(sw);
                }
                bodyWrap.appendChild(row);
            });
            var actions = el("div", "gcard-actions");
            var cont = el("button", "gbtn gbtn-primary", esc(card.continueLabel || "Continue"));
            cont.addEventListener("click", function () {
                if (panel.classList.contains("locked")) return;
                lockCard(panel);
                var value = {};
                card.fields.forEach(function (f) { value[f.key] = state[f.key]; });
                submitCard(card.key, value, composeCardMessage(card.key, value, null));
            });
            actions.appendChild(cont);
            panel.appendChild(actions);
        }

        function buildMultiCard(card, panel, bodyWrap) {
            var selected = {};
            var chips = el("div", "gchips");
            card.choices.forEach(function (ch) {
                var b = el("button", "gchip gchip-multi");
                b.innerHTML = '<span class="gcheck">✓</span><span>' + esc(ch.label) + '</span>';
                b.addEventListener("click", function () {
                    if (panel.classList.contains("locked")) return;
                    selected[ch.value] = !selected[ch.value];
                    b.classList.toggle("selected", !!selected[ch.value]);
                });
                chips.appendChild(b);
            });
            bodyWrap.appendChild(chips);
            var actions = el("div", "gcard-actions");
            var cont = el("button", "gbtn gbtn-primary", esc(card.continueLabel || "Continue"));
            cont.addEventListener("click", function () {
                if (panel.classList.contains("locked")) return;
                lockCard(panel);
                var vals = [], labels = [];
                card.choices.forEach(function (ch) { if (selected[ch.value]) { vals.push(ch.value); labels.push(ch.label); } });
                submitCard(card.key, vals, composeMustHaves(labels));
            });
            actions.appendChild(cont);
            if (card.allowSkip) {
                var skip = el("button", "gbtn gbtn-ghost", esc(card.skipLabel || "Skip"));
                skip.addEventListener("click", function () {
                    if (panel.classList.contains("locked")) return;
                    lockCard(panel);
                    submitCard(card.key, [], composeMustHaves([]));
                });
                actions.appendChild(skip);
            }
            panel.appendChild(actions);
        }

        function renderCard(card, aiRow) {
            if (!card) { activeCardRow = null; return; }
            var panel = el("div", "gcard");
            panel.setAttribute("data-key", card.key);
            panel.innerHTML = '<div class="gcard-head"><span class="gcard-ico">' + (ICON_EMOJI[card.icon] || "✨") + '</span>' +
                '<div class="gcard-htext"><div class="gcard-title">' + esc(card.title) + '</div>' +
                (card.subtitle ? '<div class="gcard-sub">' + esc(card.subtitle) + '</div>' : '') + '</div></div>';
            var bodyWrap = el("div", "gcard-body");
            panel.appendChild(bodyWrap);

            if (card.fields) buildFormCard(card, panel, bodyWrap);
            else if (card.select === "multi") buildMultiCard(card, panel, bodyWrap);
            else buildSingleCard(card, panel, bodyWrap);

            aiRow.querySelector(".body").appendChild(panel);
            activeCardRow = aiRow;
            scrollToBottom(true);
        }

        // ---------- Send ----------
        function buildSendBody(text, opts) {
            var body = { message: text, lang: lang };
            if (opts && opts.field) {
                body.field = opts.field;
                body.value = (opts.value !== undefined ? opts.value : null);
            }
            return body;
        }

        function send(text, opts) {
            if (isSending) return;
            stopPolling();
            clearSearchingRow();
            // Lock any still-open guided card before moving on (e.g. user typed free text).
            if (activeCardRow) {
                var openCard = activeCardRow.querySelector(".gcard");
                if (openCard) openCard.classList.add("locked");
                activeCardRow = null;
            }
            opts = opts || {};
            text = (text || "").trim();
            if (!text) {
                if (opts.audioUrl) text = t("voiceNote");
                else if (opts.imageUrl) text = t("imageNote");
            }
            if (!text) return;

            hideGreeting();
            addUserMessage(text, opts);
            input.value = "";
            autoGrow();
            clearAudioAttachment();
            clearImageAttachment();
            isSending = true;
            updateSendState();

            var typing = showTyping();

            fetch("/chat/send", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "X-CSRF-TOKEN": token
                },
                body: JSON.stringify(buildSendBody(text, opts))
            }).then(function (r) {
                if (!r.ok) throw new Error("HTTP " + r.status);
                return r.json();
            }).then(function (d) {
                typing.remove();
                var aiRow = addAIMessage(d.reply || "");
                if (d.card) renderCard(d.card, aiRow);
                else renderChips(d.options, aiRow);
                if (d.status === "searching" || d.search_id != null) {
                    // Real async search has started — poll /chat/status for results.
                    startSearch();
                } else if (d.results && d.results.length) {
                    // Legacy/instant fallback (results already in the send response).
                    renderResults(d.results);
                    if (d.summary) renderSummary(d.summary);
                    if (d.status === "done") renderNewSearch();
                }
            }).catch(function () {
                typing.remove();
                addAIMessage(t("sendError"));
            }).then(function () {
                isSending = false;
                updateSendState();
            });
        }

        // ---------- Reset ----------
        function resetChat() {
            stopPolling();
            clearSearchingRow();
            activeCardRow = null;
            messages.innerHTML = "";
            greeting.style.display = "";
            greeting.classList.remove("compact");
            clearAudioAttachment();
            clearImageAttachment();
            input.value = "";
            autoGrow();
            updateSendState();
            scroll.scrollTop = 0;
            input.focus();
            // Clear server state, then re-fetch the first guided card.
            fetch("/chat/reset", {
                method: "POST",
                headers: { "Accept": "application/json", "X-CSRF-TOKEN": token }
            }).catch(function () {}).then(function () { bootstrapCards(); });
        }

        // ---------- Composer behaviour ----------
        function autoGrow() {
            input.style.height = "auto";
            input.style.height = Math.min(input.scrollHeight, 168) + "px";
        }
        function updateSendState() {
            var has = input.value.trim().length > 0 || !!pendingAudioUrl || !!pendingImageUrl;
            sendBtn.disabled = !has || isSending;
        }
        input.addEventListener("input", function () { autoGrow(); updateSendState(); });
        input.addEventListener("keydown", function (e) {
            if (e.key === "Enter" && !e.shiftKey) {
                e.preventDefault();
                if (!sendBtn.disabled) doSend();
            }
        });
        sendBtn.addEventListener("click", function () { if (!sendBtn.disabled) doSend(); });
        function doSend() {
            send(input.value, { audioUrl: pendingAudioUrl, imageUrl: pendingImageUrl });
        }

        // ---------- Attachment previews ----------
        function refreshAttachRow() {
            var show = (imageChip.style.display !== "none") || (audioChip.style.display !== "none");
            attachRow.classList.toggle("show", show);
        }
        function setAudioAttachment(url) {
            pendingAudioUrl = url;
            attachAudio.src = url;
            audioChip.style.display = "flex";
            refreshAttachRow();
            updateSendState();
        }
        function clearAudioAttachment() {
            pendingAudioUrl = null;
            attachAudio.removeAttribute("src");
            if (attachAudio.load) attachAudio.load();
            audioChip.style.display = "none";
            refreshAttachRow();
            updateSendState();
        }
        function setImageAttachment(url) {
            pendingImageUrl = url;
            imageThumb.src = url;
            imageChip.style.display = "flex";
            refreshAttachRow();
            updateSendState();
        }
        function clearImageAttachment() {
            pendingImageUrl = null;
            imageThumb.removeAttribute("src");
            imageChip.style.display = "none";
            refreshAttachRow();
            updateSendState();
        }
        audioX.addEventListener("click", function () { clearAudioAttachment(); });
        imageX.addEventListener("click", function () { clearImageAttachment(); });

        // ---------- Attach menu ----------
        function toggleMenu(force) {
            var show = (force === undefined) ? !attachMenu.classList.contains("show") : force;
            attachMenu.classList.toggle("show", show);
        }
        attachBtn.addEventListener("click", function (e) {
            e.stopPropagation();
            toggleMenu();
        });
        document.addEventListener("click", function (e) {
            if (!attachMenu.contains(e.target) && e.target !== attachBtn) toggleMenu(false);
        });
        menuPhoto.addEventListener("click", function () { toggleMenu(false); imageInput.click(); });
        menuLocation.addEventListener("click", function () { toggleMenu(false); openMap(); });

        imageInput.addEventListener("change", function () {
            var file = imageInput.files && imageInput.files[0];
            if (!file) return;
            setImageAttachment(URL.createObjectURL(file));
            imageInput.value = "";
            input.focus();
        });

        // ---------- Language switch ----------
        langSwitch.addEventListener("click", function (e) {
            var btn = e.target.closest("button[data-lang]");
            if (!btn) return;
            lang = btn.getAttribute("data-lang");
            Array.prototype.forEach.call(langSwitch.children, function (c) {
                c.classList.toggle("active", c === btn);
            });
            applyStatic();
            // Re-render the currently open guided card (reply + card) in the new language.
            if (activeCardRow && !isSending) {
                activeCardRow.remove();
                activeCardRow = null;
                fetchCard().then(function (d) {
                    var aiRow = addAIMessage(d.reply || "");
                    if (d.card) renderCard(d.card, aiRow);
                    if (d.status === "searching") startSearch();
                }).catch(function () {});
            }
            input.focus();
        });

        newChatBtn.addEventListener("click", function () { resetChat(); });

        // ---------- Audio: record + speech-to-text ----------
        function setRecordingUI(on) {
            isRecording = on;
            micBtn.classList.toggle("recording", on);
            listeningHint.classList.toggle("show", on && speechSupported);
            micBtn.title = micDenied ? t("micDeniedTip") : (on ? t("micStopTip") : t("micTip"));
            micBtn.querySelector(".mic-ico").innerHTML = on
                ? '<rect x="6" y="6" width="12" height="12" rx="2.5" fill="currentColor" stroke="none"/>'
                : '<path d="M12 2a3 3 0 0 0-3 3v6a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/><path d="M19 10v1a7 7 0 0 1-14 0v-1M12 18v4M8 22h8"/>';
        }

        function startRecording() {
            if (!micSupported) {
                micDenied = true; micBtn.disabled = true; micBtn.title = t("micDeniedTip");
                toast(t("micDeniedTip"));
                return;
            }
            navigator.mediaDevices.getUserMedia({ audio: true }).then(function (stream) {
                micStream = stream;
                audioChunks = [];
                var opts = {};
                if (window.MediaRecorder && MediaRecorder.isTypeSupported) {
                    if (MediaRecorder.isTypeSupported("audio/webm")) opts.mimeType = "audio/webm";
                    else if (MediaRecorder.isTypeSupported("audio/mp4")) opts.mimeType = "audio/mp4";
                }
                try { mediaRecorder = new MediaRecorder(stream, opts); }
                catch (err) { mediaRecorder = new MediaRecorder(stream); }

                mediaRecorder.ondataavailable = function (e) { if (e.data && e.data.size) audioChunks.push(e.data); };
                mediaRecorder.onstop = function () {
                    if (audioChunks.length) {
                        var blob = new Blob(audioChunks, { type: mediaRecorder.mimeType || "audio/webm" });
                        if (blob.size) setAudioAttachment(URL.createObjectURL(blob));
                    }
                    if (micStream) { micStream.getTracks().forEach(function (tr) { tr.stop(); }); micStream = null; }
                };
                mediaRecorder.start();
                setRecordingUI(true);
                startSpeech();
            }).catch(function () {
                micDenied = true; micBtn.disabled = true; micBtn.title = t("micDeniedTip");
                toast(t("micDeniedTip"));
            });
        }

        function startSpeech() {
            if (!speechSupported) { toast(t("speechUnsupported")); return; }
            try {
                recognition = new SR();
                recognition.lang = SPEECH_LANG[lang] || "en-US";
                recognition.interimResults = true;
                recognition.continuous = true;
                speechBase = input.value ? input.value.replace(/\s+$/, "") + " " : "";
                recognition.onresult = function (e) {
                    var finalT = "", interimT = "";
                    for (var i = e.resultIndex; i < e.results.length; i++) {
                        var tr = e.results[i][0].transcript;
                        if (e.results[i].isFinal) finalT += tr;
                        else interimT += tr;
                    }
                    if (finalT) speechBase += finalT.replace(/\s+$/, "") + " ";
                    input.value = (speechBase + interimT).replace(/\s+/g, " ").trimStart();
                    autoGrow();
                    updateSendState();
                };
                recognition.onerror = function (ev) {
                    if (ev.error === "not-allowed" || ev.error === "service-not-allowed") {
                        micDenied = true; micBtn.disabled = true; micBtn.title = t("micDeniedTip");
                    }
                };
                recognition.onend = function () { recognition = null; };
                recognition.start();
            } catch (err) { recognition = null; }
        }

        function stopRecording() {
            setRecordingUI(false);
            if (recognition) { try { recognition.stop(); } catch (e) {} }
            if (mediaRecorder && mediaRecorder.state !== "inactive") {
                try { mediaRecorder.stop(); } catch (e) {}
            }
            input.value = input.value.trim();
            autoGrow();
            updateSendState();
            input.focus();
        }

        micBtn.addEventListener("click", function () {
            if (micDenied) { toast(t("micDeniedTip")); return; }
            if (isRecording) stopRecording();
            else startRecording();
        });

        // ---------- Location map (Leaflet, lazy-loaded) ----------
        var leafletPromise = null;
        var mapObj = null, mapMarker = null, mapCircle = null, mapInited = false;

        function loadLeaflet() {
            if (window.L) return Promise.resolve();
            if (leafletPromise) return leafletPromise;
            leafletPromise = new Promise(function (resolve, reject) {
                var css = document.createElement("link");
                css.rel = "stylesheet";
                css.href = "https://unpkg.com/leaflet@1.9.4/dist/leaflet.css";
                document.head.appendChild(css);
                var s = document.createElement("script");
                s.src = "https://unpkg.com/leaflet@1.9.4/dist/leaflet.js";
                s.onload = function () { resolve(); };
                s.onerror = function () { reject(new Error("leaflet")); };
                document.head.appendChild(s);
            });
            return leafletPromise;
        }

        function currentRadiusKm() { return parseFloat(radiusSlider.value); }

        function initMap() {
            if (mapInited) return;
            mapInited = true;
            mapObj = L.map("leafletMap", { zoomControl: true }).setView(TASHKENT, 13);
            L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
                maxZoom: 19,
                attribution: "© OpenStreetMap"
            }).addTo(mapObj);
            mapMarker = L.marker(TASHKENT, { draggable: true }).addTo(mapObj);
            mapCircle = L.circle(TASHKENT, {
                radius: currentRadiusKm() * 1000,
                color: "#13a06a", fillColor: "#13a06a", fillOpacity: 0.12, weight: 2
            }).addTo(mapObj);
            mapMarker.on("drag", function (e) { mapCircle.setLatLng(e.target.getLatLng()); });
            mapObj.on("click", function (e) { setMapPoint([e.latlng.lat, e.latlng.lng], false); });
        }

        function setMapPoint(ll, recenter) {
            if (!mapObj) return;
            mapMarker.setLatLng(ll);
            mapCircle.setLatLng(ll);
            if (recenter) mapObj.setView(ll, 14);
        }

        function openMap() {
            mapModal.classList.add("show");
            loadLeaflet().then(function () {
                initMap();
                setTimeout(function () { if (mapObj) mapObj.invalidateSize(); }, 80);
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function (pos) {
                        setMapPoint([pos.coords.latitude, pos.coords.longitude], true);
                    }, function () {}, { timeout: 8000, maximumAge: 60000 });
                }
            }).catch(function () {
                toast(t("mapError"));
                closeMap();
            });
        }
        function closeMap() { mapModal.classList.remove("show"); }

        radiusSlider.addEventListener("input", function () {
            radiusVal.textContent = fmtKm(currentRadiusKm());
            if (mapCircle) mapCircle.setRadius(currentRadiusKm() * 1000);
        });

        mapConfirm.addEventListener("click", function () {
            var R = currentRadiusKm();
            var lat = TASHKENT[0], lng = TASHKENT[1];
            if (mapMarker) { var p = mapMarker.getLatLng(); lat = p.lat; lng = p.lng; }
            var text = t("locationMsg").replace("{R}", R);
            var locLine = "📍 " + lat.toFixed(5) + ", " + lng.toFixed(5) + " · " + fmtKm(R);
            closeMap();
            send(text, { locationLine: locLine });
        });
        mapClose.addEventListener("click", closeMap);
        mapCancel.addEventListener("click", closeMap);
        mapModal.addEventListener("click", function (e) { if (e.target === mapModal) closeMap(); });
        document.addEventListener("keydown", function (e) {
            if (e.key === "Escape" && mapModal.classList.contains("show")) closeMap();
        });

        // ---------- Init ----------
        if (!micSupported) {
            micDenied = true;
            micBtn.disabled = true;
            micBtn.title = t("micDeniedTip");
        }
        applyStatic();
        updateSendState();
        autoGrow();
        input.focus();
        // Greeting first, then load the first guided card from the backend.
        bootstrapCards();
    })();
    </script>
</body>
</html>
