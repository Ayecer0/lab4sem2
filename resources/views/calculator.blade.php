<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Калькулятор</title>
    <style>
        body { font-family: Arial, sans-serif; background: #fff; color: #000; margin: 40px auto; width: 300px; }
        .history { min-height: 18px; text-align: right; font-size: 13px; color: #666; word-break: break-all; }
        #display { width: 100%; padding: 10px; font-size: 22px; text-align: right; border: 2px solid #000; box-sizing: border-box; }
        .error { margin-top: 6px; padding: 6px; border: 1px solid #000; font-size: 14px; }
        .keys { display: grid; grid-template-columns: repeat(5, 1fr); gap: 4px; margin-top: 10px; }
        button { padding: 12px 0; font-size: 16px; background: #fff; color: #000; border: 1px solid #000; cursor: pointer; }
        button:hover { background: #000; color: #fff; }
        .eq { grid-column: span 4; background: #000; color: #fff; }
        .eq:hover { background: #fff; color: #000; }
    </style>
</head>
<body>
<form method="POST" action="{{ route('calculate') }}" autocomplete="off">
    @csrf

    <div class="history">{{ ($result !== null || $error) ? $expression . ($result !== null ? ' =' : '') : '' }}</div>

    <input type="text" name="expression" id="display" maxlength="255"
           data-calculated="{{ $result !== null ? '1' : '0' }}"
           value="{{ $result ?? ($error ? $expression : '') }}" autofocus>

    @if ($error)
        <div class="error">{{ $error }}</div>
    @endif

    <div class="keys">
        <button type="button" id="btnClear">C</button>
        <button type="button" id="btnBack">&larr;</button>
        <button type="button" data-insert="(">(</button>
        <button type="button" data-insert=")" data-continue>)</button>
        <button type="button" data-insert="/" data-continue>/</button>

        <button type="button" data-insert="7">7</button>
        <button type="button" data-insert="8">8</button>
        <button type="button" data-insert="9">9</button>
        <button type="button" data-insert="*" data-continue>*</button>
        <button type="button" data-insert="^" data-continue>^</button>

        <button type="button" data-insert="4">4</button>
        <button type="button" data-insert="5">5</button>
        <button type="button" data-insert="6">6</button>
        <button type="button" data-insert="-" data-continue>-</button>
        <button type="button" data-insert="sqrt(">&radic;</button>

        <button type="button" data-insert="1">1</button>
        <button type="button" data-insert="2">2</button>
        <button type="button" data-insert="3">3</button>
        <button type="button" data-insert="+" data-continue>+</button>
        <button type="button" data-insert="!" data-continue>!</button>

        <button type="button" data-insert="0">0</button>
        <button type="button" data-insert=".">.</button>
        <button type="button" data-insert="pi">&pi;</button>
        <button type="button" data-insert="e">e</button>
        <button type="button" data-insert="ln(">ln</button>

        <button type="button" data-insert="log(">log</button>
        <button type="submit" class="eq">=</button>
    </div>
</form>

<script>
    const display = document.getElementById('display');
    let calculated = display.dataset.calculated === '1'; 

    
    document.querySelectorAll('[data-insert]').forEach(btn => {
        btn.addEventListener('click', () => {
            if (calculated && !btn.hasAttribute('data-continue')) display.value = '';
            calculated = false;
            display.value += btn.dataset.insert;
            display.focus();
        });
    });

    document.getElementById('btnClear').onclick = () => {
        display.value = '';
        calculated = false;
        display.focus();
    };

    document.getElementById('btnBack').onclick = () => {
        display.value = display.value.slice(0, -1);
        calculated = false;
        display.focus();
    };

    
    display.addEventListener('keydown', e => {
        if (e.ctrlKey || e.metaKey || e.altKey) return;
        if (e.key === 'Backspace' || e.key === 'Delete') { calculated = false; return; }
        if (e.key.length !== 1) return;
        if (calculated && /[0-9a-z(.]/i.test(e.key)) display.value = '';
        calculated = false;
    });

    
    display.addEventListener('input', () => {
        const clean = display.value.toLowerCase().replace(/,/g, '.').replace(/[^0-9a-z+\-*\/^().!]/g, '');
        if (clean !== display.value) display.value = clean;
    });

    display.setSelectionRange(display.value.length, display.value.length);
</script>
</body>
</html>