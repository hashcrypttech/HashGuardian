@extends('hashguardian::layouts.app')

@section('title', 'Htop Monitor')

@section('content')
    <div class="htop-page">
        {{-- System Meters --}}
        <div class="htop-meters">
            <div class="htop-meters-left" id="htop-cpu-meters">
                <div class="htop-loading">Loading CPU data...</div>
            </div>
            <div class="htop-meters-right">
                <div id="htop-mem-bar"></div>
                <div id="htop-swap-bar"></div>
                <div class="htop-sys-info" id="htop-sys-info"></div>
            </div>
        </div>

        {{-- Trend Charts --}}
        <div class="htop-trends">
            <div class="htop-trend-panel">
                <div class="htop-trend-header">
                    <span class="htop-trend-title">CPU History</span>
                    <span class="htop-trend-val" id="htop-cpu-trend-val">--</span>
                </div>
                <canvas id="htop-cpu-chart" height="55"></canvas>
            </div>
            <div class="htop-trend-panel">
                <div class="htop-trend-header">
                    <span class="htop-trend-title">Memory History</span>
                    <span class="htop-trend-val" id="htop-mem-trend-val">--</span>
                </div>
                <canvas id="htop-mem-chart" height="55"></canvas>
            </div>
        </div>

        {{-- Zombie Detail Panel --}}
        <div class="htop-zombie-panel" id="htop-zombie-panel" style="display:none;">
            <div class="htop-zombie-header">
                <div class="htop-zombie-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <span>Zombie Processes</span>
                    <span class="htop-zombie-count" id="htop-zombie-badge">0</span>
                </div>
                <button class="htop-tool-btn" id="htop-zombie-close" title="Close">&times;</button>
            </div>
            <p class="htop-zombie-desc">Zombie processes have completed execution but still have entries in the process table because their parent has not read their exit status. The parent process (PPID) must collect the exit status to clean them up.</p>
            <div id="htop-zombie-list"></div>
        </div>

        {{-- Toolbar --}}
        <div class="htop-toolbar">
            <div class="htop-toolbar-group">
                <div class="htop-tool-item">
                    <kbd>/</kbd>
                    <input type="text" id="htop-filter-input" class="htop-filter-input" placeholder="Filter processes...">
                </div>
                <div class="htop-tool-item">
                    <label class="htop-tool-label">Sort</label>
                    <select id="htop-sort-select" class="htop-select">
                        <option value="cpu" selected>CPU%</option>
                        <option value="mem">MEM%</option>
                        <option value="pid">PID</option>
                        <option value="res">RES</option>
                        <option value="swap">SWAP</option>
                        <option value="virt">VIRT</option>
                        <option value="time">TIME+</option>
                        <option value="user">USER</option>
                        <option value="command">Command</option>
                    </select>
                    <button id="htop-sort-dir-btn" class="htop-tool-btn htop-sort-arrow" title="Toggle sort direction">&#9660;</button>
                </div>
                <button id="htop-tree-btn" class="htop-tool-btn" title="Toggle tree view">
                    <kbd>t</kbd> Tree
                </button>
            </div>
            <div class="htop-toolbar-group">
                <div class="htop-tool-item">
                    <label class="htop-tool-label">Refresh</label>
                    <select id="htop-refresh-select" class="htop-select">
                        <option value="1000">1s</option>
                        <option value="2000" selected>2s</option>
                        <option value="5000">5s</option>
                        <option value="10000">10s</option>
                    </select>
                </div>
                <button id="htop-pause-btn" class="htop-tool-btn" title="Pause/Resume">
                    <kbd>p</kbd> Pause
                </button>
                <button id="htop-top-btn" class="htop-tool-btn" title="Scroll to top">&#8593; Top</button>
            </div>
        </div>

        {{-- Process Table --}}
        <div class="htop-table-wrap" id="htop-table-wrap">
            <table class="htop-table">
                <thead>
                    <tr>
                        <th data-col="pid" class="htop-th-r">PID</th>
                        <th data-col="user">USER</th>
                        <th data-col="pri" class="htop-th-r">PRI</th>
                        <th data-col="ni" class="htop-th-r">NI</th>
                        <th data-col="virt" class="htop-th-r">VIRT</th>
                        <th data-col="res" class="htop-th-r">RES</th>
                        <th data-col="swap" class="htop-th-r">SWAP</th>
                        <th data-col="state" class="htop-th-c">S</th>
                        <th data-col="cpu" class="htop-th-r htop-th-sorted">CPU%<span class="htop-sort-ind">&#9660;</span></th>
                        <th data-col="mem" class="htop-th-r">MEM%<span class="htop-sort-ind"></span></th>
                        <th data-col="time" class="htop-th-r">TIME+</th>
                        <th data-col="command">Command</th>
                    </tr>
                </thead>
                <tbody id="htop-tbody">
                    <tr><td colspan="12" class="htop-loading-cell">Loading processes...</td></tr>
                </tbody>
            </table>
        </div>

        {{-- Status Bar --}}
        <div class="htop-status" id="htop-status">
            <span class="htop-status-item">Processes: <strong id="htop-status-count">-</strong></span>
            <span class="htop-status-item">Shown: <strong id="htop-status-shown">-</strong></span>
            <span class="htop-status-item" id="htop-status-paused" style="display:none;color:var(--wd-warning);">PAUSED</span>
            <span class="htop-status-right" id="htop-status-time">--</span>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function() {
    var DATA_URL = '{{ route("hashguardian.htop.data") }}';

    var S = {
        prevCpu: null,
        cpuPct: null,
        memory: null,
        system: null,
        processes: [],
        treeMap: {},
        sortCol: 'cpu',
        sortDir: 'desc',
        treeMode: false,
        filterText: '',
        refreshMs: 2000,
        paused: false,
        cpuHistory: [],
        memHistory: [],
        maxHistory: 150,
        timer: null,
        fetchActive: false
    };

    // ── Init ──
    function init() {
        setupToolbar();
        setupColumnHeaders();
        setupKeyboard();
        fetchData();
    }

    // ── Data Fetching ──
    function fetchData() {
        if (S.fetchActive) return;
        S.fetchActive = true;

        fetch(DATA_URL, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            S.fetchActive = false;
            processData(data);
            scheduleNext();
        })
        .catch(function(e) {
            S.fetchActive = false;
            console.error('Htop fetch error:', e);
            scheduleNext();
        });
    }

    function scheduleNext() {
        clearTimeout(S.timer);
        if (!S.paused) {
            S.timer = setTimeout(fetchData, S.refreshMs);
        }
    }

    // ── Process incoming data ──
    function processData(data) {
        var cpuNow = data.cpu || {};
        if (S.prevCpu && cpuNow.cores) {
            S.cpuPct = computeCpuDelta(cpuNow, S.prevCpu);
        }
        S.prevCpu = cpuNow;

        S.memory = data.memory || {};
        S.system = data.system || {};
        S.processes = data.processes || [];
        S.treeMap = data.tree || {};

        renderCpuMeters();
        renderMemBar();
        renderSwapBar();
        renderSysInfo();
        renderProcessTable();
        updateHistory();
        renderTrendCharts();
        renderStatus();

        var zombiePanel = document.getElementById('htop-zombie-panel');
        if (zombiePanel && zombiePanel.style.display !== 'none') {
            renderZombiePanel();
        }
    }

    // ── CPU delta calculation ──
    function computeCpuDelta(curr, prev) {
        var result = { cores: [], total: null };

        if (curr.total && prev.total) {
            result.total = coreDeltas(curr.total, prev.total);
        }

        var coreKeys = Object.keys(curr.cores || {});
        coreKeys.sort(function(a, b) { return parseInt(a) - parseInt(b); });
        for (var i = 0; i < coreKeys.length; i++) {
            var k = coreKeys[i];
            if (prev.cores && prev.cores[k]) {
                result.cores.push(coreDeltas(curr.cores[k], prev.cores[k]));
            }
        }

        return result;
    }

    function coreDeltas(c, p) {
        var du = c.user - p.user;
        var dn = c.nice - p.nice;
        var ds = c.system - p.system;
        var di = c.idle - p.idle;
        var dw = c.iowait - p.iowait;
        var dq = c.irq - p.irq;
        var df = c.softirq - p.softirq;
        var total = du + dn + ds + di + dw + dq + df;
        if (total <= 0) total = 1;
        return {
            user:    (du / total) * 100,
            nice:    (dn / total) * 100,
            system:  (ds / total) * 100,
            idle:    (di / total) * 100,
            iowait:  (dw / total) * 100,
            irq:     (dq / total) * 100,
            softirq: (df / total) * 100,
            total:   100 - ((di + dw) / total) * 100
        };
    }

    // ── Render CPU Meters ──
    function renderCpuMeters() {
        var el = document.getElementById('htop-cpu-meters');
        if (!el) return;

        if (!S.cpuPct || !S.cpuPct.cores.length) {
            el.innerHTML = '<div class="htop-loading">Calculating CPU usage...</div>';
            return;
        }

        var cores = S.cpuPct.cores;
        var twoCol = cores.length > 8;
        var half = Math.ceil(cores.length / 2);
        var html = '';

        if (twoCol) {
            html += '<div class="htop-cpu-cols">';
            html += '<div class="htop-cpu-col">';
            for (var i = 0; i < half; i++) {
                html += cpuBarHtml(i, cores[i]);
            }
            html += '</div><div class="htop-cpu-col">';
            for (var j = half; j < cores.length; j++) {
                html += cpuBarHtml(j, cores[j]);
            }
            html += '</div></div>';
        } else {
            for (var k = 0; k < cores.length; k++) {
                html += cpuBarHtml(k, cores[k]);
            }
        }

        el.innerHTML = html;
    }

    function cpuBarHtml(idx, d) {
        var pct = Math.min(100, d.total).toFixed(1);
        var segments = '';
        if (d.nice > 0.3)    segments += '<div class="htop-seg htop-seg-nice" style="width:' + d.nice.toFixed(2) + '%"></div>';
        if (d.user > 0.3)    segments += '<div class="htop-seg htop-seg-user" style="width:' + d.user.toFixed(2) + '%"></div>';
        if (d.system > 0.3)  segments += '<div class="htop-seg htop-seg-sys" style="width:' + d.system.toFixed(2) + '%"></div>';
        var irqTotal = d.irq + d.softirq;
        if (irqTotal > 0.3)  segments += '<div class="htop-seg htop-seg-irq" style="width:' + irqTotal.toFixed(2) + '%"></div>';
        if (d.iowait > 0.3)  segments += '<div class="htop-seg htop-seg-iowait" style="width:' + d.iowait.toFixed(2) + '%"></div>';

        return '<div class="htop-bar-row">' +
            '<span class="htop-bar-lbl">' + idx + '</span>' +
            '<div class="htop-bar-track">' + segments + '</div>' +
            '<span class="htop-bar-pct' + (pct > 80 ? ' htop-bar-pct-hi' : '') + '">' + pct + '%</span>' +
            '</div>';
    }

    // ── Render Memory Bar ──
    function renderMemBar() {
        var el = document.getElementById('htop-mem-bar');
        if (!el || !S.memory.total) return;

        var m = S.memory;
        var total = m.total || 1;
        var usedPct = (m.used / total) * 100;
        var bufPct = (m.buffers / total) * 100;
        var cachePct = (m.cached / total) * 100;

        el.innerHTML = '<div class="htop-bar-row">' +
            '<span class="htop-bar-lbl">Mem</span>' +
            '<div class="htop-bar-track">' +
                '<div class="htop-seg htop-seg-mem-used" style="width:' + usedPct.toFixed(2) + '%"></div>' +
                '<div class="htop-seg htop-seg-mem-buf" style="width:' + bufPct.toFixed(2) + '%"></div>' +
                '<div class="htop-seg htop-seg-mem-cache" style="width:' + cachePct.toFixed(2) + '%"></div>' +
            '</div>' +
            '<span class="htop-bar-pct">' + fmtBytes(m.used + m.buffers + m.cached) + '/' + fmtBytes(total) + '</span>' +
            '</div>';
    }

    // ── Render Swap Bar ──
    function renderSwapBar() {
        var el = document.getElementById('htop-swap-bar');
        if (!el) return;

        var m = S.memory;
        if (!m.swap_total) {
            el.innerHTML = '<div class="htop-bar-row">' +
                '<span class="htop-bar-lbl">Swp</span>' +
                '<div class="htop-bar-track"></div>' +
                '<span class="htop-bar-pct">N/A</span></div>';
            return;
        }

        var pct = (m.swap_used / m.swap_total) * 100;

        el.innerHTML = '<div class="htop-bar-row">' +
            '<span class="htop-bar-lbl">Swp</span>' +
            '<div class="htop-bar-track">' +
                '<div class="htop-seg htop-seg-swap" style="width:' + pct.toFixed(2) + '%"></div>' +
            '</div>' +
            '<span class="htop-bar-pct">' + fmtBytes(m.swap_used) + '/' + fmtBytes(m.swap_total) + '</span>' +
            '</div>';
    }

    // ── Render System Info ──
    function renderSysInfo() {
        var el = document.getElementById('htop-sys-info');
        if (!el || !S.system) return;

        var t = S.system.tasks || {};
        var l = S.system.load_avg || [0, 0, 0];

        var zombieHtml = '';
        if (t.zombie) {
            zombieHtml = ', <span class="htop-zombie-link" id="htop-zombie-trigger" title="Click to view zombie process details">' + t.zombie + ' zombie</span>';
        }

        el.innerHTML =
            '<div class="htop-sys-row"><span class="htop-sys-label">Tasks:</span> ' +
                '<span class="htop-sys-val">' + (t.total || 0) + '</span>, ' +
                '<span style="color:var(--wd-success)">' + (t.running || 0) + ' running</span>, ' +
                '<span>' + (t.sleeping || 0) + ' sleeping</span>, ' +
                '<span>' + (t.stopped || 0) + ' stopped</span>' +
                zombieHtml +
            '</div>' +
            '<div class="htop-sys-row"><span class="htop-sys-label">Load avg:</span> ' +
                l[0].toFixed(2) + ' ' + l[1].toFixed(2) + ' ' + l[2].toFixed(2) +
            '</div>' +
            '<div class="htop-sys-row"><span class="htop-sys-label">Uptime:</span> ' +
                fmtUptime(S.system.uptime || 0) +
            '</div>';

        var trigger = document.getElementById('htop-zombie-trigger');
        if (trigger) {
            trigger.addEventListener('click', toggleZombiePanel);
        }
    }

    // ── Zombie Detail Panel ──
    function toggleZombiePanel() {
        var panel = document.getElementById('htop-zombie-panel');
        if (!panel) return;

        var isVisible = panel.style.display !== 'none';
        if (isVisible) {
            panel.style.display = 'none';
            return;
        }

        renderZombiePanel();
        panel.style.display = '';
    }

    function renderZombiePanel() {
        var listEl = document.getElementById('htop-zombie-list');
        var badgeEl = document.getElementById('htop-zombie-badge');
        if (!listEl) return;

        var zombies = S.processes.filter(function(p) { return p.state === 'Z'; });
        if (badgeEl) badgeEl.textContent = zombies.length;

        if (zombies.length === 0) {
            listEl.innerHTML = '<div class="htop-zombie-empty">No zombie processes found.</div>';
            return;
        }

        var html = '<table class="htop-zombie-table">' +
            '<thead><tr>' +
            '<th>PID</th><th>PPID</th><th>USER</th><th>CPU%</th><th>MEM%</th><th>TIME+</th><th>Command</th><th>Parent Command</th>' +
            '</tr></thead><tbody>';

        zombies.forEach(function(z) {
            var ppid = S.treeMap[String(z.pid)];
            var ppidDisplay = ppid !== undefined ? ppid : '?';

            var parentCmd = '—';
            if (ppid !== undefined) {
                var parent = S.processes.find(function(p) { return p.pid === ppid; });
                if (parent) {
                    parentCmd = esc(parent.command.length > 60 ? parent.command.substring(0, 60) + '...' : parent.command);
                }
            }

            html += '<tr>' +
                '<td class="htop-mono">' + z.pid + '</td>' +
                '<td class="htop-mono">' + ppidDisplay + '</td>' +
                '<td>' + esc(z.user) + '</td>' +
                '<td class="htop-mono">' + z.cpu.toFixed(1) + '</td>' +
                '<td class="htop-mono">' + z.mem.toFixed(1) + '</td>' +
                '<td class="htop-mono">' + esc(z.time) + '</td>' +
                '<td class="htop-td-cmd" title="' + escAttr(z.command) + '">' + esc(z.command.length > 50 ? z.command.substring(0, 50) + '...' : z.command) + '</td>' +
                '<td class="htop-td-cmd htop-parent-cmd" title="' + escAttr(parentCmd) + '">' + parentCmd + '</td>' +
                '</tr>';
        });

        html += '</tbody></table>';
        listEl.innerHTML = html;
    }

    // ── Render Process Table ──
    function renderProcessTable() {
        var tbody = document.getElementById('htop-tbody');
        if (!tbody) return;

        var list = S.processes.slice();

        if (S.filterText) {
            var q = S.filterText.toLowerCase();
            list = list.filter(function(p) {
                return p.command.toLowerCase().indexOf(q) !== -1 ||
                       p.user.toLowerCase().indexOf(q) !== -1 ||
                       String(p.pid).indexOf(q) !== -1;
            });
        }

        if (S.treeMode) {
            list = buildTreeList(list);
        } else {
            list = sortList(list);
        }

        var html = '';
        for (var i = 0; i < list.length; i++) {
            var p = list[i];
            var rowCls = '';
            if (p.state === 'R') rowCls = ' htop-row-run';
            else if (p.cpu > 80) rowCls = ' htop-row-hi';
            else if (p.state === 'Z') rowCls = ' htop-row-zombie';

            var cmdDisplay = p._treePrefix ? (esc(p._treePrefix) + esc(p.command)) : esc(p.command);

            html += '<tr class="htop-proc-row' + rowCls + '">' +
                '<td class="htop-td-r htop-mono">' + p.pid + '</td>' +
                '<td>' + esc(p.user) + '</td>' +
                '<td class="htop-td-r htop-mono">' + p.pri + '</td>' +
                '<td class="htop-td-r htop-mono">' + p.ni + '</td>' +
                '<td class="htop-td-r htop-mono">' + fmtSize(p.virt) + '</td>' +
                '<td class="htop-td-r htop-mono">' + fmtSize(p.res) + '</td>' +
                '<td class="htop-td-r htop-mono' + (p.swap > 0 ? ' htop-val-swap' : '') + '">' + fmtSize(p.swap) + '</td>' +
                '<td class="htop-td-c htop-state-' + p.state + '">' + p.state + '</td>' +
                '<td class="htop-td-r htop-mono' + cpuColor(p.cpu) + '">' + p.cpu.toFixed(1) + '</td>' +
                '<td class="htop-td-r htop-mono' + memColor(p.mem) + '">' + p.mem.toFixed(1) + '</td>' +
                '<td class="htop-td-r htop-mono">' + esc(p.time) + '</td>' +
                '<td class="htop-td-cmd" title="' + escAttr(p.command) + '">' + cmdDisplay + '</td>' +
                '</tr>';
        }

        tbody.innerHTML = html || '<tr><td colspan="12" class="htop-loading-cell">No processes found</td></tr>';

        document.getElementById('htop-status-count').textContent = S.processes.length;
        document.getElementById('htop-status-shown').textContent = list.length;
    }

    // ── Sorting ──
    function sortList(list) {
        var col = S.sortCol;
        var dir = S.sortDir === 'asc' ? 1 : -1;

        list.sort(function(a, b) {
            var va = a[col], vb = b[col];
            if (typeof va === 'string') {
                return va.localeCompare(vb) * dir;
            }
            return (va - vb) * dir;
        });

        return list;
    }

    // ── Tree Building ──
    function buildTreeList(processes) {
        var byPid = {};
        var children = {};
        var roots = [];

        processes.forEach(function(p) {
            byPid[p.pid] = p;
            children[p.pid] = [];
        });

        processes.forEach(function(p) {
            var ppid = S.treeMap[String(p.pid)];
            if (ppid !== undefined && ppid !== p.pid && byPid[ppid]) {
                children[ppid].push(p.pid);
            } else {
                roots.push(p.pid);
            }
        });

        var result = [];
        function walk(pid, depth, prefix, isLast) {
            var proc = byPid[pid];
            if (!proc) return;
            var p = Object.assign({}, proc);

            if (depth > 0) {
                p._treePrefix = prefix + (isLast ? '└─ ' : '├─ ');
            }

            result.push(p);

            var kids = children[pid] || [];
            for (var i = 0; i < kids.length; i++) {
                var childPrefix = depth > 0 ? prefix + (isLast ? '   ' : '│  ') : '';
                walk(kids[i], depth + 1, childPrefix, i === kids.length - 1);
            }
        }

        roots.forEach(function(pid) { walk(pid, 0, '', true); });
        return result;
    }

    // ── History & Trend Charts ──
    function updateHistory() {
        if (S.cpuPct && S.cpuPct.total) {
            S.cpuHistory.push(S.cpuPct.total.total);
            if (S.cpuHistory.length > S.maxHistory) S.cpuHistory.shift();
        }
        if (S.memory && S.memory.total) {
            var memPct = ((S.memory.used + S.memory.buffers + S.memory.cached) / S.memory.total) * 100;
            S.memHistory.push(memPct);
            if (S.memHistory.length > S.maxHistory) S.memHistory.shift();
        }
    }

    function renderTrendCharts() {
        drawTrendChart('htop-cpu-chart', S.cpuHistory, '#34d399', 'htop-cpu-trend-val');
        drawTrendChart('htop-mem-chart', S.memHistory, '#818cf8', 'htop-mem-trend-val');
    }

    function drawTrendChart(canvasId, data, color, valId) {
        var canvas = document.getElementById(canvasId);
        if (!canvas) return;
        var ctx = canvas.getContext('2d');
        var dpr = window.devicePixelRatio || 1;
        var rect = canvas.getBoundingClientRect();
        canvas.width = rect.width * dpr;
        canvas.height = rect.height * dpr;
        ctx.scale(dpr, dpr);

        var w = rect.width, h = rect.height;
        var pad = { top: 4, right: 4, bottom: 4, left: 4 };
        var cw = w - pad.left - pad.right;
        var ch = h - pad.top - pad.bottom;

        var isLight = document.body.classList.contains('wd-light');
        ctx.clearRect(0, 0, w, h);

        if (data.length < 2) {
            ctx.fillStyle = isLight ? '#94a3b8' : '#475569';
            ctx.font = '11px Inter,sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText('Collecting data...', w / 2, h / 2 + 3);
            return;
        }

        var maxVal = 100;
        var points = [];
        for (var i = 0; i < data.length; i++) {
            points.push({
                x: pad.left + (i / (data.length - 1)) * cw,
                y: pad.top + ch - (Math.min(data[i], maxVal) / maxVal) * ch
            });
        }

        var grad = ctx.createLinearGradient(0, pad.top, 0, pad.top + ch);
        grad.addColorStop(0, color + '30');
        grad.addColorStop(1, color + '05');

        ctx.beginPath();
        ctx.moveTo(points[0].x, points[0].y);
        for (var j = 1; j < points.length; j++) {
            var prev = points[j - 1], curr = points[j];
            var mx = (prev.x + curr.x) / 2;
            ctx.bezierCurveTo(mx, prev.y, mx, curr.y, curr.x, curr.y);
        }
        ctx.lineTo(points[points.length - 1].x, pad.top + ch);
        ctx.lineTo(points[0].x, pad.top + ch);
        ctx.closePath();
        ctx.fillStyle = grad;
        ctx.fill();

        ctx.beginPath();
        ctx.moveTo(points[0].x, points[0].y);
        for (var k = 1; k < points.length; k++) {
            var pp = points[k - 1], pc = points[k];
            var mmx = (pp.x + pc.x) / 2;
            ctx.bezierCurveTo(mmx, pp.y, mmx, pc.y, pc.x, pc.y);
        }
        ctx.strokeStyle = color;
        ctx.lineWidth = 1.5;
        ctx.stroke();

        var valEl = document.getElementById(valId);
        if (valEl && data.length) {
            valEl.textContent = data[data.length - 1].toFixed(1) + '%';
        }
    }

    // ── Toolbar & Events ──
    function setupToolbar() {
        var filterInput = document.getElementById('htop-filter-input');
        if (filterInput) {
            filterInput.addEventListener('input', function() {
                S.filterText = this.value;
                renderProcessTable();
            });
        }

        var sortSelect = document.getElementById('htop-sort-select');
        if (sortSelect) {
            sortSelect.addEventListener('change', function() {
                S.sortCol = this.value;
                updateSortIndicators();
                renderProcessTable();
            });
        }

        var sortDirBtn = document.getElementById('htop-sort-dir-btn');
        if (sortDirBtn) {
            sortDirBtn.addEventListener('click', function() {
                S.sortDir = S.sortDir === 'desc' ? 'asc' : 'desc';
                this.innerHTML = S.sortDir === 'desc' ? '&#9660;' : '&#9650;';
                updateSortIndicators();
                renderProcessTable();
            });
        }

        var treeBtn = document.getElementById('htop-tree-btn');
        if (treeBtn) {
            treeBtn.addEventListener('click', function() {
                S.treeMode = !S.treeMode;
                this.classList.toggle('htop-tool-active', S.treeMode);
                renderProcessTable();
            });
        }

        var refreshSelect = document.getElementById('htop-refresh-select');
        if (refreshSelect) {
            refreshSelect.addEventListener('change', function() {
                S.refreshMs = parseInt(this.value);
                if (!S.paused) {
                    clearTimeout(S.timer);
                    S.timer = setTimeout(fetchData, S.refreshMs);
                }
            });
        }

        var pauseBtn = document.getElementById('htop-pause-btn');
        if (pauseBtn) {
            pauseBtn.addEventListener('click', togglePause);
        }

        var topBtn = document.getElementById('htop-top-btn');
        if (topBtn) {
            topBtn.addEventListener('click', function() {
                var wrap = document.getElementById('htop-table-wrap');
                if (wrap) wrap.scrollTop = 0;
            });
        }

        var zombieClose = document.getElementById('htop-zombie-close');
        if (zombieClose) {
            zombieClose.addEventListener('click', function() {
                var panel = document.getElementById('htop-zombie-panel');
                if (panel) panel.style.display = 'none';
            });
        }
    }

    function togglePause() {
        S.paused = !S.paused;
        var btn = document.getElementById('htop-pause-btn');
        var indicator = document.getElementById('htop-status-paused');
        if (btn) {
            btn.classList.toggle('htop-tool-active', S.paused);
            btn.innerHTML = '<kbd>p</kbd> ' + (S.paused ? 'Resume' : 'Pause');
        }
        if (indicator) indicator.style.display = S.paused ? '' : 'none';
        if (!S.paused) {
            clearTimeout(S.timer);
            fetchData();
        }
    }

    // ── Column header click sorting ──
    function setupColumnHeaders() {
        var ths = document.querySelectorAll('.htop-table thead th[data-col]');
        ths.forEach(function(th) {
            th.style.cursor = 'pointer';
            th.addEventListener('click', function() {
                var col = this.getAttribute('data-col');
                if (S.sortCol === col) {
                    S.sortDir = S.sortDir === 'desc' ? 'asc' : 'desc';
                } else {
                    S.sortCol = col;
                    S.sortDir = 'desc';
                }
                var select = document.getElementById('htop-sort-select');
                if (select) select.value = col;
                var dirBtn = document.getElementById('htop-sort-dir-btn');
                if (dirBtn) dirBtn.innerHTML = S.sortDir === 'desc' ? '&#9660;' : '&#9650;';
                updateSortIndicators();
                renderProcessTable();
            });
        });
    }

    function updateSortIndicators() {
        var ths = document.querySelectorAll('.htop-table thead th[data-col]');
        ths.forEach(function(th) {
            var col = th.getAttribute('data-col');
            var ind = th.querySelector('.htop-sort-ind');
            th.classList.toggle('htop-th-sorted', col === S.sortCol);
            if (ind) {
                ind.innerHTML = col === S.sortCol ? (S.sortDir === 'desc' ? '&#9660;' : '&#9650;') : '';
            }
        });
    }

    // ── Keyboard Shortcuts ──
    function setupKeyboard() {
        document.addEventListener('keydown', function(e) {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') {
                if (e.key === 'Escape') {
                    e.target.blur();
                    e.preventDefault();
                }
                return;
            }

            if (e.key === '/') {
                e.preventDefault();
                var input = document.getElementById('htop-filter-input');
                if (input) input.focus();
            } else if (e.key === 't') {
                e.preventDefault();
                var treeBtn = document.getElementById('htop-tree-btn');
                if (treeBtn) treeBtn.click();
            } else if (e.key === 'p') {
                e.preventDefault();
                togglePause();
            } else if (e.key === ' ') {
                e.preventDefault();
                var dirBtn = document.getElementById('htop-sort-dir-btn');
                if (dirBtn) dirBtn.click();
            }
        });
    }

    // ── Status Bar ──
    function renderStatus() {
        var el = document.getElementById('htop-status-time');
        if (el) {
            var now = new Date();
            el.textContent = now.toLocaleTimeString();
        }
    }

    // ── Helpers ──
    function fmtBytes(bytes) {
        if (!bytes || bytes <= 0) return '0B';
        var units = ['B', 'K', 'M', 'G', 'T'];
        var i = Math.floor(Math.log(bytes) / Math.log(1024));
        i = Math.min(i, units.length - 1);
        var val = bytes / Math.pow(1024, i);
        return (i === 0 ? val : val.toFixed(1)) + units[i];
    }

    function fmtSize(bytes) {
        if (!bytes || bytes <= 0) return '0';
        if (bytes < 1024) return bytes + 'B';
        if (bytes < 1048576) return Math.round(bytes / 1024) + 'K';
        if (bytes < 1073741824) return (bytes / 1048576).toFixed(1) + 'M';
        return (bytes / 1073741824).toFixed(2) + 'G';
    }

    function fmtUptime(secs) {
        var d = Math.floor(secs / 86400);
        var h = Math.floor((secs % 86400) / 3600);
        var m = Math.floor((secs % 3600) / 60);
        var s = secs % 60;
        var parts = [];
        if (d > 0) parts.push(d + (d === 1 ? ' day' : ' days'));
        parts.push(pad2(h) + ':' + pad2(m) + ':' + pad2(s));
        return parts.join(', ');
    }

    function pad2(n) { return n < 10 ? '0' + n : '' + n; }

    function cpuColor(v) {
        if (v > 80) return ' htop-val-hi';
        if (v > 40) return ' htop-val-mid';
        if (v > 0.5) return ' htop-val-lo';
        return '';
    }

    function memColor(v) {
        if (v > 50) return ' htop-val-hi';
        if (v > 20) return ' htop-val-mid';
        return '';
    }

    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s || '';
        return d.innerHTML;
    }

    function escAttr(s) {
        return (s || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    // ── Boot ──
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
@endpush
