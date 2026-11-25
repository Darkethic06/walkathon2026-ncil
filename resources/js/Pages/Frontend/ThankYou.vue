<script setup>
import { ref } from "vue";

const props = defineProps({
    registration: { type: Object, required: true },
});

const downloading = ref(false);

function loadImage(src) {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.onload = () => resolve(img);
        img.onerror = reject;
        img.src = src;
    });
}

function roundRect(ctx, x, y, w, h, r) {
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.arcTo(x + w, y, x + w, y + h, r);
    ctx.arcTo(x + w, y + h, x, y + h, r);
    ctx.arcTo(x, y + h, x, y, r);
    ctx.arcTo(x, y, x + w, y, r);
    ctx.closePath();
}

function triggerDownload(href, filename) {
    const a = document.createElement("a");
    a.href = href;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
}

// Builds a printable bib card (PNG) in the browser: logo, bib number, name,
// event details and the QR code, in the walkathon colours.
async function downloadBibCard() {
    downloading.value = true;
    try {
        const { bib, fullName, qr } = props.registration;
        await Promise.all([
            document.fonts.load('800 200px "Montserrat"'),
            document.fonts.load('700 40px "Figtree"'),
            document.fonts.load('italic 700 40px "Playfair Display"'),
        ]).catch(() => {});
        const [logo, qrImg] = await Promise.all([
            loadImage("/images/nephrocare-logo.png"),
            loadImage(qr),
        ]);

        const W = 1000, H = 1400;
        const canvas = document.createElement("canvas");
        canvas.width = W;
        canvas.height = H;
        const ctx = canvas.getContext("2d");

        // page + card
        ctx.fillStyle = "#dbf6fe";
        ctx.fillRect(0, 0, W, H);
        ctx.fillStyle = "#ffffff";
        roundRect(ctx, 40, 40, W - 80, H - 80, 40);
        ctx.fill();

        // gold top strip
        ctx.save();
        roundRect(ctx, 40, 40, W - 80, H - 80, 40);
        ctx.clip();
        ctx.fillStyle = "#eba92b";
        ctx.fillRect(40, 40, W - 80, 14);
        ctx.restore();

        // logo
        const logoW = 520, logoH = (logo.height / logo.width) * logoW;
        ctx.drawImage(logo, (W - logoW) / 2, 100, logoW, logoH);

        ctx.textAlign = "center";

        ctx.fillStyle = "#0d2c4a";
        ctx.font = '800 54px "Montserrat", sans-serif';
        ctx.fillText("ANNUAL WALKATHON 2026", W / 2, 270);

        ctx.fillStyle = "#0f6478";
        ctx.font = 'italic 700 36px "Playfair Display", serif';
        ctx.fillText("Walk for Kidney, Walk for Health", W / 2, 325);

        // bib number panel
        const grad = ctx.createLinearGradient(0, 380, 0, 680);
        grad.addColorStop(0, "#0f6478");
        grad.addColorStop(1, "#0b3b4d");
        ctx.fillStyle = grad;
        roundRect(ctx, 110, 375, W - 220, 300, 28);
        ctx.fill();

        ctx.fillStyle = "#eba92b";
        ctx.font = '700 30px "Figtree", sans-serif';
        ctx.fillText("B I B   N U M B E R", W / 2, 430);

        ctx.fillStyle = "#ffffff";
        let size = 200;
        ctx.font = `800 ${size}px "Montserrat", sans-serif`;
        while (ctx.measureText(bib).width > W - 300 && size > 80) {
            size -= 10;
            ctx.font = `800 ${size}px "Montserrat", sans-serif`;
        }
        ctx.fillText(bib, W / 2, 625);

        // name
        ctx.fillStyle = "#0d2c4a";
        ctx.font = '700 46px "Figtree", sans-serif';
        ctx.fillText(fullName, W / 2, 750, W - 200);

        // QR with dashed frame
        const qrSize = 380, qrX = (W - qrSize) / 2, qrY = 800;
        ctx.setLineDash([14, 10]);
        ctx.lineWidth = 4;
        ctx.strokeStyle = "#1f9db5";
        roundRect(ctx, qrX - 20, qrY - 20, qrSize + 40, qrSize + 40, 24);
        ctx.stroke();
        ctx.setLineDash([]);
        ctx.drawImage(qrImg, qrX, qrY, qrSize, qrSize);

        // details
        ctx.fillStyle = "#33495a";
        ctx.font = '600 30px "Figtree", sans-serif';
        ctx.fillText("Sunday, 13 December 2026  •  Salt Lake Stadium, Kolkata", W / 2, 1255);
        ctx.fillStyle = "#5a6d7c";
        ctx.font = '500 24px "Figtree", sans-serif';
        ctx.fillText("NephroCare in association with Vivacity Multispeciality Hospital", W / 2, 1300);

        triggerDownload(canvas.toDataURL("image/png"), `walkathon-bib-${bib}.png`);
    } finally {
        downloading.value = false;
    }
}

function downloadQr() {
    triggerDownload(props.registration.qr, `walkathon-bib-${props.registration.bib}-qr.png`);
}
</script>

<template>
    <main class="thanks">
        <div class="wrap thanks-grid">
            <div class="thanks-text">
                <span class="thanks-check" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M5 12.500l4.500 4.500L19 7.500" /></svg>
                </span>
                <p class="d-eyebrow"><span>Registration confirmed</span></p>
                <h2>Thank you, <em>{{ registration.firstName }}!</em></h2>
                <p class="thanks-lead">
                    You're registered for the <strong>Annual Walkathon 2026</strong>. Here is your bib number and QR code.
                    Show the QR code at the bib collection desk.
                </p>
                <p class="thanks-mail">
                    We've also sent this to <strong>{{ registration.email }}</strong>.
                    If you can't find it, check your spam folder.
                </p>

                <ul class="thanks-facts">
                    <li><b>Date</b><span>Sunday, 13 December 2026</span></li>
                    <li><b>Start &amp; finish</b><span>Salt Lake Stadium, Kolkata</span></li>
                    <li><b>Distance</b><span>4 km walk</span></li>
                </ul>

                <p class="small">Reporting time and bib collection details will be shared by email and WhatsApp closer to the event.</p>
                <Link class="btn outline" href="/">&larr; Back to home</Link>
            </div>

            <div class="bib-card">
                <img class="bib-logo" src="/images/nephrocare-logo.png" alt="Nephrocare" width="834" height="109" />
                <p class="bib-event">Annual Walkathon 2026</p>
                <div class="bib-number">
                    <small>Bib number</small>
                    <b>{{ registration.bib }}</b>
                </div>
                <p class="bib-name">{{ registration.fullName }}</p>
                <div class="bib-qr">
                    <img :src="registration.qr" :alt="`QR code for bib ${registration.bib}`" width="360" height="360" />
                </div>
                <div class="bib-actions">
                    <button class="btn gold" type="button" :disabled="downloading" @click="downloadBibCard">
                        {{ downloading ? "Preparing..." : "Download bib card" }}
                    </button>
                    <button class="btn outline" type="button" @click="downloadQr">Download QR code</button>
                </div>
            </div>
        </div>
    </main>
</template>
