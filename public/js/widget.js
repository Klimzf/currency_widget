document.addEventListener('DOMContentLoaded', () => {

    let widgetRefreshInterval = null;

    async function loadWidgetData() {
        try {
            const response = await fetch('/api/currencies');
            const data = await response.json();
            renderWidget(data);
        } catch (e) {
            console.error(e);
            document.getElementById('widget').innerHTML = '<p>Ошибка загрузки</p>';
        }
    }

    function renderWidget(currencies) {
        const widget = document.getElementById('widget');
        if (!currencies.length) {
            widget.innerHTML = '<p>Нет данных для отображения</p>';
            return;
        }
        let html = '';
        currencies.forEach(c => {
            const val = Number(c.value);
            if (isNaN(val)) {
                html += `<div class="currency-row"><span class="currency-name">${c.code}</span><span class="currency-value">Нет данных</span><span class="currency-change"></span></div>`;
                return;
            }
            const currencyName = c.name || c.code;
            let changeHtml = '';
            const ch = c.change !== null ? Number(c.change) : null;
            if (ch === null) {
                changeHtml = '<span style="color:gray">■ нет данных</span>';
            } else if (ch === 0) {
                changeHtml = '<span style="color:gray">■ 0.0000</span>';
            } else {
                const cls = ch > 0 ? 'up' : 'down';
                const arrow = ch > 0 ? '▲' : '▼';
                changeHtml = `<span class="${cls}">${arrow} ${Math.abs(ch).toFixed(4)}</span>`;
            }
            html += `
                <div class="currency-row">
                    <span class="currency-name"><strong>${c.code}</strong> (${currencyName})</span>
                    <span class="currency-value">${val.toFixed(4)}</span>
                    <span class="currency-change">${changeHtml}</span>
                </div>
            `;
        });
        widget.innerHTML = html;
    }

    async function loadSettings() {
        try {
            const resp = await fetch('/api/settings');
            const s = await resp.json();
            document.getElementById('display-currencies').value = s.display_currencies.join(',');
            document.getElementById('fetch-currencies').value = s.fetch_currencies.join(',');
            document.getElementById('update-interval').value = s.update_interval;
            startAutoRefresh(s.update_interval);
        } catch (e) {
            console.error(e);
        }
    }

    function startAutoRefresh(seconds) {
        if (widgetRefreshInterval) clearInterval(widgetRefreshInterval);
        if (seconds > 0) {
            widgetRefreshInterval = setInterval(loadWidgetData, seconds * 1000);
        }
    }

    // Обработчик кнопки «Сохранить»
    document.getElementById('settings-form').addEventListener('submit', async (e) => {
        e.preventDefault();

        const displayCurrencies = document.getElementById('display-currencies').value
            .split(',')
            .map(s => s.trim())
            .filter(s => s);
        const fetchCurrencies = document.getElementById('fetch-currencies').value
            .split(',')
            .map(s => s.trim())
            .filter(s => s);
        const interval = parseInt(document.getElementById('update-interval').value, 10) || 60;

        try {
            const resp = await fetch('/api/settings', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    display_currencies: displayCurrencies,
                    fetch_currencies: fetchCurrencies,
                    update_interval: interval
                })
            });
            if (resp.ok) {
                document.getElementById('settings-message').innerHTML = '<span style="color:green">Настройки сохранены</span>';
  
                startAutoRefresh(interval);
        
                loadWidgetData();
            } else {
                document.getElementById('settings-message').innerHTML = '<span style="color:red">Ошибка сохранения</span>';
            }
        } catch (e) {
            console.error(e);
            document.getElementById('settings-message').innerHTML = '<span style="color:red">Ошибка сети</span>';
        }
    });

    loadSettings();
    loadWidgetData();

});