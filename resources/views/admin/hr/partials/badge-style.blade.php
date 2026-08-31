<style>
    *, *::before, *::after { box-sizing: border-box; }
    body { margin: 0; background: #f4f5f3; color: #202628;
           font-family: 'Manrope', ui-sans-serif, system-ui, sans-serif; }

    .toolbar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between;
               gap: 12px; padding: 16px 24px; background: #fff; border-bottom: 1px solid #e3e7e9; }
    .toolbar a { font-size: 13px; color: #47565f; text-decoration: none; }
    .toolbar select { border: 1px solid #a2aeb4; border-radius: 8px; padding: 6px 10px; font-size: 13px; }
    .toolbar button { border: 0; border-radius: 999px; background: #202628; color: #fff;
                      padding: 8px 18px; font-size: 13px; font-weight: 600; cursor: pointer; }
    .hint { max-width: 900px; margin: 16px auto 0; padding: 0 24px; font-size: 12px; color: #77878f; text-align: center; }

    /* ---- Format carte CR80 / ISO-7810 ID-1 : 85,60 × 53,98 mm ---- */
    .sheet { display: grid; grid-template-columns: repeat(2, 85.6mm); gap: 6mm 8mm;
             justify-content: center; padding: 14mm 0 20mm; }
    .sheet--one { grid-template-columns: 85.6mm; padding: 22mm 0; }

    .card { position: relative; width: 85.6mm; height: 53.98mm; border-radius: 3.2mm;
            background: #374249; color: #fff; overflow: hidden;
            box-shadow: 0 8px 24px rgba(17, 24, 39, .20);
            -webkit-print-color-adjust: exact; print-color-adjust: exact;
            break-inside: avoid; page-break-inside: avoid; }
    .card::before { content: ""; position: absolute; top: 0; bottom: 0; left: 0; width: 4mm; background: #de6443; }
    .card::after  { content: ""; position: absolute; right: -14mm; top: -14mm; width: 34mm; height: 34mm;
                    border-radius: 50%; background: rgba(255, 255, 255, .05); }

    .card__in { position: absolute; inset: 0; padding: 4mm 4mm 3mm 7.5mm; display: flex; flex-direction: column; }

    .brand { display: flex; align-items: center; gap: 2mm; }
    .brand img { height: 6mm; width: auto; filter: brightness(0) invert(1); }
    .brand span { font-family: 'Fraunces', Georgia, serif; font-size: 7.5pt; letter-spacing: .03em; color: #c3ced2; }

    .body { flex: 1; display: flex; gap: 3.5mm; margin-top: 2.6mm; min-height: 0; }
    .identity { flex: 1; min-width: 0; display: flex; flex-direction: column; }
    .name { font-family: 'Fraunces', Georgia, serif; font-weight: 600; font-size: 12pt; line-height: 1.12;
            overflow-wrap: anywhere; }
    .role { font-size: 7pt; color: #a2aeb4; margin-top: 1mm; overflow-wrap: anywhere; }
    .service { margin-top: auto; font-size: 6pt; font-weight: 700; letter-spacing: .16em;
               text-transform: uppercase; color: #c3ced2; }
    .service b { display: block; font-size: 8.5pt; letter-spacing: .02em; text-transform: none; color: #fff; margin-top: .4mm; }
    .mat { margin-top: 1.8mm; align-self: flex-start; font-family: ui-monospace, 'SF Mono', Menlo, monospace;
           font-size: 8.5pt; font-weight: 700; letter-spacing: .14em;
           background: #fff; color: #374249; padding: .8mm 2.4mm; border-radius: 1mm; }

    .qrwrap { display: flex; flex-direction: column; align-items: center; flex-shrink: 0; }
    .qr { width: 24mm; height: 24mm; background: #fff; border-radius: 1.8mm; padding: 1.4mm; }
    .qr img { width: 100%; height: 100%; display: block; }
    .qr__cap { margin-top: 1.2mm; font-size: 5pt; letter-spacing: .18em; text-transform: uppercase; color: #a2aeb4; }

    .foot { font-size: 5pt; letter-spacing: .02em; color: #596d7a; }

    @media print {
        body { background: #fff; }
        .no-print { display: none !important; }
        .sheet { gap: 4mm 6mm; padding: 0; }
        .sheet--one { padding: 0; }
        .card { box-shadow: none; outline: .2mm dashed #c3c8d4; outline-offset: 1mm; }
        @page { size: A4 portrait; margin: 12mm; }
    }
</style>
