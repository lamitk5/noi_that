import * as THREE from 'three';
import { OrbitControls } from 'three/examples/jsm/controls/OrbitControls.js';
import { RoundedBoxGeometry } from 'three/examples/jsm/geometries/RoundedBoxGeometry.js';

const METAL = '#3b3b3d';
const LINEN = '#f4f1ea';

const FLOORS = {
    oak: { kind: 'plank', colors: ['#d9c3a2', '#cfb692', '#e2ceb0', '#c8ad86'], edge: '#cdb593' },
    walnut: { kind: 'plank', colors: ['#8a6446', '#7b583c', '#956f4f', '#6e4e35'], edge: '#6b4b33' },
    stone: { kind: 'tile', colors: ['#dcd8d1', '#d4d0c8', '#e2ded8'], edge: '#c9c4bb' },
};

function seeded(seed) {
    let value = seed % 2147483647;
    return () => {
        value = (value * 16807) % 2147483647;
        return (value - 1) / 2147483646;
    };
}

function floorTexture(key) {
    const style = FLOORS[key] || FLOORS.oak;
    const canvas = document.createElement('canvas');
    canvas.width = 1024;
    canvas.height = 1024;
    const ctx = canvas.getContext('2d');
    const random = seeded(key.length * 7919 + 17);
    const metre = 512;

    if (style.kind === 'tile') {
        const tile = metre * 0.6;
        for (let y = 0; y < 1024; y += tile) {
            for (let x = 0; x < 1024; x += tile) {
                ctx.fillStyle = style.colors[Math.floor(random() * style.colors.length)];
                ctx.fillRect(x, y, tile, tile);
                for (let i = 0; i < 40; i++) {
                    ctx.fillStyle = `rgba(120,110,100,${0.03 + random() * 0.04})`;
                    ctx.beginPath();
                    ctx.arc(x + random() * tile, y + random() * tile, 4 + random() * 18, 0, Math.PI * 2);
                    ctx.fill();
                }
            }
        }
        ctx.strokeStyle = style.edge;
        ctx.lineWidth = 3;
        for (let p = 0; p <= 1024; p += tile) {
            ctx.beginPath();
            ctx.moveTo(p, 0);
            ctx.lineTo(p, 1024);
            ctx.moveTo(0, p);
            ctx.lineTo(1024, p);
            ctx.stroke();
        }
    } else {
        const row = metre * 0.2;
        for (let y = 0; y < 1024; y += row) {
            let x = -random() * metre;
            while (x < 1024) {
                const length = metre * (0.8 + random() * 0.8);
                ctx.fillStyle = style.colors[Math.floor(random() * style.colors.length)];
                ctx.fillRect(x, y, length, row);
                for (let g = 0; g < 7; g++) {
                    ctx.strokeStyle = `rgba(60,40,25,${0.05 + random() * 0.06})`;
                    ctx.lineWidth = 1 + random() * 2;
                    ctx.beginPath();
                    const gy = y + random() * row;
                    ctx.moveTo(x, gy);
                    ctx.bezierCurveTo(x + length * 0.3, gy + (random() - 0.5) * 10, x + length * 0.7, gy + (random() - 0.5) * 10, x + length, gy);
                    ctx.stroke();
                }
                ctx.fillStyle = 'rgba(40,25,15,0.35)';
                ctx.fillRect(x, y, 2, row);
                x += length;
            }
            ctx.fillStyle = 'rgba(40,25,15,0.3)';
            ctx.fillRect(0, y, 1024, 2);
        }
    }

    const texture = new THREE.CanvasTexture(canvas);
    texture.colorSpace = THREE.SRGBColorSpace;
    texture.wrapS = THREE.RepeatWrapping;
    texture.wrapT = THREE.RepeatWrapping;
    texture.anisotropy = 8;
    return texture;
}

const spriteCache = new Map();

function textSprite(text, { height = 0.14, color = '#2f2a24', background = 'rgba(255,255,255,0.94)' } = {}) {
    const key = `${text}|${color}|${background}`;
    let texture = spriteCache.get(key);
    let ratio;
    if (!texture) {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        const font = '600 40px "DM Sans", system-ui, sans-serif';
        ctx.font = font;
        const width = Math.ceil(ctx.measureText(text).width) + 36;
        canvas.width = width;
        canvas.height = 64;
        ctx.font = font;
        ctx.fillStyle = background;
        const r = 30;
        ctx.beginPath();
        ctx.moveTo(r, 0);
        ctx.arcTo(width, 0, width, 64, r);
        ctx.arcTo(width, 64, 0, 64, r);
        ctx.arcTo(0, 64, 0, 0, r);
        ctx.arcTo(0, 0, width, 0, r);
        ctx.closePath();
        ctx.fill();
        ctx.fillStyle = color;
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(text, width / 2, 34);
        texture = new THREE.CanvasTexture(canvas);
        texture.colorSpace = THREE.SRGBColorSpace;
        texture.userData.ratio = width / 64;
        spriteCache.set(key, texture);
    }
    ratio = texture.userData.ratio;
    const sprite = new THREE.Sprite(new THREE.SpriteMaterial({ map: texture, depthTest: false, transparent: true }));
    sprite.scale.set(height * ratio, height, 1);
    sprite.renderOrder = 20;
    sprite.userData.noPick = true;
    return sprite;
}

function material(color, options = {}) {
    return new THREE.MeshStandardMaterial({
        color,
        roughness: options.roughness ?? 0.72,
        metalness: options.metalness ?? 0,
        transparent: options.opacity !== undefined,
        opacity: options.opacity ?? 1,
        emissive: options.emissive ?? '#000000',
        emissiveIntensity: options.emissiveIntensity ?? 0,
    });
}

function shade(hex, amount) {
    const color = new THREE.Color(hex);
    const hsl = {};
    color.getHSL(hsl);
    color.setHSL(hsl.h, hsl.s, Math.max(0, Math.min(1, hsl.l + amount)));
    return `#${color.getHexString()}`;
}

class Builder {
    constructor(colors) {
        this.group = new THREE.Group();
        this.main = colors.main;
        this.accent = colors.accent;
    }

    box(color, x, y, z, w, h, d, radius = 0, options = {}) {
        if (w <= 0.002 || h <= 0.002 || d <= 0.002) return null;
        const r = Math.min(radius, w / 2 - 0.001, h / 2 - 0.001, d / 2 - 0.001);
        const geometry = r > 0.003 ? new RoundedBoxGeometry(w, h, d, 3, r) : new THREE.BoxGeometry(w, h, d);
        return this.mesh(geometry, color, x, y, z, options);
    }

    cylinder(color, x, y, z, radiusTop, radiusBottom, h, options = {}) {
        const geometry = new THREE.CylinderGeometry(radiusTop, radiusBottom, h, options.segments ?? 40);
        return this.mesh(geometry, color, x, y, z, options);
    }

    mesh(geometry, color, x, y, z, options = {}) {
        const mesh = new THREE.Mesh(geometry, material(color, options));
        mesh.position.set(x, y, z);
        mesh.castShadow = options.castShadow ?? true;
        mesh.receiveShadow = true;
        this.group.add(mesh);
        return mesh;
    }
}

function buildFurniture(shape, w, d, h, colors, extra = {}) {
    const b = new Builder(colors);
    const main = colors.main;
    const accent = colors.accent;
    const soft = shade(main, 0.06);

    const legs = (inset, legH, size = 0.04, color = accent) => {
        for (const sx of [-1, 1]) {
            for (const sz of [-1, 1]) {
                b.box(color, sx * (w / 2 - inset), legH / 2, sz * (d / 2 - inset), size, legH, size, 0.008);
            }
        }
    };

    const cabinetBody = (low) => {
        const plinth = Math.min(0.06, h * 0.1);
        b.box(shade(accent, -0.08), 0, plinth / 2, 0, w - 0.04, plinth, d - 0.04);
        b.box(accent, 0, plinth + (h - plinth) / 2, 0, w, h - plinth, d, 0.012);
        const doors = low ? Math.max(2, Math.round(w / 0.6)) : Math.max(1, Math.round(w / 0.5));
        const gap = 0.008;
        const doorW = (w - 0.03 - gap * (doors - 1)) / doors;
        const doorH = (h - plinth) - 0.03;
        for (let i = 0; i < doors; i++) {
            const x = -w / 2 + 0.015 + doorW / 2 + i * (doorW + gap);
            b.box(main, x, plinth + 0.015 + doorH / 2, d / 2 + 0.006, doorW, doorH, 0.014, 0.004);
            const handleH = Math.min(0.18, doorH * 0.35);
            const handleX = low ? x : x + (i % 2 === 0 ? doorW / 2 - 0.04 : -doorW / 2 + 0.04);
            const handleY = low ? plinth + doorH - 0.05 : plinth + doorH * 0.55;
            if (low) {
                b.box(METAL, handleX, handleY, d / 2 + 0.018, Math.min(0.16, doorW * 0.4), 0.012, 0.012, 0, { metalness: 0.8, roughness: 0.3 });
            } else {
                b.box(METAL, handleX, handleY, d / 2 + 0.018, 0.012, handleH, 0.012, 0, { metalness: 0.8, roughness: 0.3 });
            }
        }
    };

    switch (shape) {
        case 'sofa':
        case 'armchair': {
            const single = shape === 'armchair' || w < 1.2;
            const legH = 0.08;
            const seatTop = Math.min(h * 0.52, 0.46);
            const back = Math.min(0.22, d * 0.26);
            const arm = Math.min(single ? 0.14 : 0.18, w * 0.16);
            const armH = Math.min(h * 0.75, seatTop + 0.2);
            legs(0.08, legH, 0.04, shade(accent, -0.15));
            b.box(main, 0, legH + (seatTop - 0.12 - legH) / 2, 0, w, seatTop - 0.12 - legH, d, 0.03);
            b.box(main, 0, (seatTop - 0.12 + h) / 2, -d / 2 + back / 2, w, h - seatTop + 0.12, back, 0.05);
            for (const sx of [-1, 1]) {
                b.box(main, sx * (w / 2 - arm / 2), (legH + armH) / 2, back / 2 * 0.5, arm, armH - legH, d - back * 0.5, 0.05);
            }
            const count = single ? 1 : Math.max(2, Math.round((w - arm * 2) / 0.65));
            const inner = w - arm * 2 - 0.02;
            const cw = inner / count;
            for (let i = 0; i < count; i++) {
                const x = -inner / 2 + cw * (i + 0.5);
                b.box(soft, x, seatTop - 0.06, back / 2 + 0.01, cw - 0.015, 0.13, d - back - 0.04, 0.05);
                b.box(soft, x, seatTop + (h - seatTop) * 0.45, -d / 2 + back + 0.07, cw - 0.03, (h - seatTop) * 0.8, 0.14, 0.06);
            }
            break;
        }
        case 'pouf': {
            const radius = Math.min(w, d) / 2;
            b.cylinder(main, 0, h / 2 - 0.01, 0, radius, radius * 0.97, h - 0.02);
            b.cylinder(soft, 0, h - 0.015, 0, radius * 0.96, radius * 0.98, 0.03);
            if (h > 0.5) {
                b.box(accent, 0, h * 0.75, -radius + 0.06, radius * 1.6, h * 0.5, 0.1, 0.05);
            }
            break;
        }
        case 'stool': {
            const radius = Math.min(w, d) / 2;
            b.cylinder(main, 0, h - 0.035, 0, radius, radius * 0.95, 0.07);
            b.cylinder(METAL, 0, (h - 0.07) / 2, 0, 0.025, 0.03, h - 0.07, { metalness: 0.8, roughness: 0.3 });
            const ring = new THREE.Mesh(new THREE.TorusGeometry(radius * 0.7, 0.01, 10, 40), material(METAL, { metalness: 0.8, roughness: 0.3 }));
            ring.rotation.x = Math.PI / 2;
            ring.position.y = h * 0.35;
            ring.castShadow = true;
            b.group.add(ring);
            b.cylinder(METAL, 0, 0.012, 0, radius * 0.75, radius * 0.8, 0.024, { metalness: 0.8, roughness: 0.3 });
            break;
        }
        case 'office': {
            const seat = Math.min(0.5, h * 0.45);
            b.box(main, 0, seat, 0.02, w * 0.8, 0.08, d * 0.75, 0.035);
            b.box(main, 0, seat + (h - seat) / 2 + 0.03, -d * 0.33, w * 0.72, h - seat - 0.04, 0.07, 0.035);
            b.cylinder(METAL, 0, seat / 2, 0, 0.025, 0.025, seat - 0.08, { metalness: 0.8, roughness: 0.3 });
            for (let i = 0; i < 5; i++) {
                const angle = (i / 5) * Math.PI * 2;
                const arm = b.box(METAL, Math.cos(angle) * w * 0.2, 0.05, Math.sin(angle) * w * 0.2, w * 0.42, 0.03, 0.04, 0, { metalness: 0.7, roughness: 0.35 });
                arm.rotation.y = -angle;
                b.mesh(new THREE.SphereGeometry(0.025, 12, 10), '#1f1f1f', Math.cos(angle) * w * 0.4, 0.025, Math.sin(angle) * w * 0.4);
            }
            break;
        }
        case 'chair': {
            const seat = Math.min(0.46, h * 0.5);
            legs(0.04, seat - 0.03, 0.035);
            b.box(accent, 0, seat - 0.015, 0, w * 0.95, 0.04, d * 0.95, 0.01);
            b.box(main, 0, seat + 0.02, 0.01, w * 0.88, 0.05, d * 0.85, 0.02);
            b.box(accent, 0, (h + seat) / 2, -d / 2 + 0.03, w * 0.92, h - seat, 0.04, 0.015);
            break;
        }
        case 'bench': {
            const top = Math.min(0.1, h * 0.25);
            legs(0.05, h - top, 0.045);
            b.box(accent, 0, h - top - 0.02, 0, w, 0.04, d, 0.01);
            b.box(main, 0, h - top / 2 + 0.01, 0, w * 0.97, top, d * 0.94, 0.03);
            if (h > 0.4) {
                b.box(shade(accent, -0.05), 0, (h - top) * 0.3, 0, w - 0.12, 0.025, d - 0.1);
            }
            break;
        }
        case 'bed': {
            const frameH = 0.32;
            const mattress = Math.max(0.18, Math.min(0.25, h - frameH + 0.1));
            const headH = Math.max(0.9, h + 0.55);
            b.box(accent, 0, frameH / 2, 0.02, w, frameH, d - 0.04, 0.03);
            b.box(main, 0, headH / 2, -d / 2 + 0.05, w + 0.04, headH, 0.1, 0.04);
            b.box(LINEN, 0, frameH + mattress / 2 - 0.02, 0.05, w - 0.06, mattress, d - 0.16, 0.05);
            b.box(soft, 0, frameH + mattress - 0.01, d * 0.18, w - 0.02, 0.05, d * 0.58, 0.025);
            const pillows = w > 1.3 ? 2 : 1;
            const pw = (w - 0.2) / pillows;
            for (let i = 0; i < pillows; i++) {
                b.box('#ffffff', -w / 2 + 0.1 + pw * (i + 0.5), frameH + mattress + 0.05, -d / 2 + 0.3, pw - 0.06, 0.12, 0.38, 0.05);
            }
            break;
        }
        case 'round-table': {
            const radius = Math.min(w, d) / 2;
            b.cylinder(main, 0, h - 0.02, 0, radius, radius, 0.04, { roughness: 0.4 });
            b.cylinder(accent, 0, (h - 0.04) / 2, 0, radius * 0.12, radius * 0.16, h - 0.04);
            b.cylinder(accent, 0, 0.015, 0, radius * 0.45, radius * 0.5, 0.03);
            break;
        }
        case 'table': {
            const top = Math.min(0.045, h * 0.1);
            b.box(main, 0, h - top / 2, 0, w, top, d, 0.008, { roughness: 0.45 });
            legs(Math.min(0.07, w * 0.12), h - top, 0.05, accent);
            if (d < 0.5) {
                b.box(accent, 0, h * 0.25, 0, w - 0.14, 0.025, d - 0.08);
            }
            break;
        }
        case 'pendant': {
            const radius = Math.max(0.1, Math.min(w, d) / 2);
            const cord = extra.cord ?? 0.4;
            b.cylinder(METAL, 0, h + cord / 2, 0, 0.006, 0.006, cord, { castShadow: false });
            const lampShade = new THREE.Mesh(new THREE.CylinderGeometry(radius * 0.35, radius, h * 0.7, 40, 1, true), material(main, { roughness: 0.5 }));
            lampShade.material.side = THREE.DoubleSide;
            lampShade.position.y = h * 0.55;
            lampShade.castShadow = true;
            b.group.add(lampShade);
            b.mesh(new THREE.SphereGeometry(Math.min(0.06, radius * 0.3), 20, 14), '#fff6dc', 0, h * 0.25, 0, { emissive: '#ffe7b0', emissiveIntensity: 1.2, castShadow: false });
            break;
        }
        case 'floor-lamp':
        case 'table-lamp': {
            const radius = Math.max(0.08, Math.min(w, d) / 2);
            const shadeH = Math.min(0.32, h * 0.3);
            b.cylinder(METAL, 0, 0.012, 0, radius * 0.6, radius * 0.65, 0.024, { metalness: 0.7, roughness: 0.3 });
            b.cylinder(METAL, 0, (h - shadeH) / 2, 0, 0.012, 0.012, h - shadeH, { metalness: 0.7, roughness: 0.3 });
            b.cylinder(main, 0, h - shadeH / 2, 0, radius * 0.8, radius, shadeH, { emissive: '#ffe9c4', emissiveIntensity: 0.35 });
            break;
        }
        case 'wall-lamp': {
            b.box(METAL, 0, h / 2, -d / 2 + 0.01, w * 0.6, h * 0.6, 0.02, 0, { metalness: 0.7, roughness: 0.3 });
            b.box(main, 0, h / 2, 0.01, w, h, Math.max(0.04, d - 0.02), 0.01, { emissive: '#ffe9c4', emissiveIntensity: 0.6 });
            break;
        }
        case 'mirror': {
            const depth = Math.max(d, 0.03);
            b.box(accent, 0, h / 2, 0, w, h, depth, 0.01);
            b.box('#dfe8ec', 0, h / 2, depth / 2 + 0.002, w - 0.06, h - 0.06, 0.006, 0, { metalness: 0.9, roughness: 0.06 });
            break;
        }
        case 'curtain': {
            const folds = Math.max(6, Math.round(w / 0.12));
            const fw = w / folds;
            b.cylinder(METAL, 0, h - 0.02, 0, 0.012, 0.012, w + 0.1, { metalness: 0.7, roughness: 0.3 }).rotation.z = Math.PI / 2;
            for (let i = 0; i < folds; i++) {
                const x = -w / 2 + fw * (i + 0.5);
                b.box(i % 2 ? main : soft, x, (h - 0.04) / 2, i % 2 ? 0.015 : -0.015, fw + 0.01, h - 0.05, 0.025, 0.01, { roughness: 0.9 });
            }
            break;
        }
        case 'rug': {
            b.box(accent, 0, 0.004, 0, w, 0.008, d, 0.004, { castShadow: false, roughness: 0.95 });
            b.box(main, 0, 0.01, 0, w - 0.08, 0.006, d - 0.08, 0.003, { castShadow: false, roughness: 0.95 });
            break;
        }
        case 'screen': {
            const panel = w / 3;
            [-1, 0, 1].forEach((i) => {
                const z = i === 0 ? -0.05 : 0.03;
                b.box(accent, i * panel, h / 2, z, panel - 0.015, h, 0.03, 0.006);
                const slats = Math.max(4, Math.round(h / 0.12));
                for (let s = 1; s < slats; s++) {
                    b.box(shade(accent, -0.08), i * panel, (h / slats) * s, z + 0.017, panel - 0.05, 0.012, 0.006, 0, { castShadow: false });
                }
            });
            break;
        }
        case 'glass': {
            b.box(METAL, 0, 0.015, 0, w, 0.03, 0.04, 0, { metalness: 0.8, roughness: 0.3 });
            b.box(METAL, -w / 2 + 0.01, h / 2, 0, 0.02, h, 0.03, 0, { metalness: 0.8, roughness: 0.3 });
            b.box('#cfe6ee', 0, h / 2, 0, w - 0.02, h - 0.03, 0.01, 0, { opacity: 0.35, roughness: 0.05, castShadow: false });
            break;
        }
        case 'tub': {
            b.box('#fbfbf9', 0, h / 2, 0, w, h, d, 0.08, { roughness: 0.25 });
            b.box('#dce6ea', 0, h - 0.03, 0, w - 0.14, 0.04, d - 0.14, 0.06, { roughness: 0.1, castShadow: false });
            b.cylinder(METAL, w / 2 - 0.12, h + 0.06, -d / 2 + 0.08, 0.015, 0.015, 0.12, { metalness: 0.9, roughness: 0.2 });
            break;
        }
        case 'shelf': {
            b.box(accent, -w / 2 + 0.015, h / 2, 0, 0.03, h, d);
            b.box(accent, w / 2 - 0.015, h / 2, 0, 0.03, h, d);
            b.box(shade(accent, -0.06), 0, h / 2, -d / 2 + 0.008, w, h, 0.016);
            const levels = Math.max(2, Math.round(h / 0.36));
            const books = ['#7c4a3a', '#3f5a4b', '#c9a66b', '#2f3d55', '#a3573c', main];
            for (let i = 0; i <= levels; i++) {
                const y = Math.max(0.012, Math.min(h - 0.012, (h * i) / levels));
                b.box(accent, 0, y, 0, w - 0.03, 0.024, d - 0.01);
                if (i < levels) {
                    let x = -w / 2 + 0.06;
                    let n = 0;
                    while (x < w / 2 - 0.15 && n < 9) {
                        const bw = 0.025 + ((i * 7 + n * 3) % 5) * 0.008;
                        const bh = Math.min(h / levels - 0.06, 0.18 + ((i + n) % 4) * 0.03);
                        if ((i + n) % 6 !== 5) {
                            b.box(books[(i * 3 + n) % books.length], x + bw / 2, y + 0.012 + bh / 2, 0, bw, bh, Math.max(0.05, d * 0.7), 0, { castShadow: false });
                        }
                        x += bw + 0.006;
                        n++;
                    }
                }
            }
            break;
        }
        case 'nightstand': {
            b.box(accent, 0, h / 2, 0, w, h, d, 0.01);
            const drawers = h > 0.45 ? 3 : 2;
            const gap = 0.008;
            const fh = (h - 0.04 - gap * (drawers - 1)) / drawers;
            for (let i = 0; i < drawers; i++) {
                const y = 0.02 + fh / 2 + i * (fh + gap);
                b.box(main, 0, y, d / 2 + 0.006, w - 0.03, fh, 0.012, 0.004);
                b.box(METAL, 0, y, d / 2 + 0.016, Math.min(0.1, w * 0.35), 0.01, 0.01, 0, { metalness: 0.8, roughness: 0.3 });
            }
            break;
        }
        case 'tv':
            cabinetBody(true);
            break;
        case 'island':
            b.box(accent, 0, (h - 0.04) / 2, 0, w * 0.94, h - 0.04, d * 0.9, 0.01);
            b.box(main, 0, h - 0.02, 0, w, 0.04, d, 0.006, { roughness: 0.3 });
            break;
        case 'cabinet':
            cabinetBody(h < 1);
            break;
        default:
            b.box(main, 0, h / 2, 0, w, h, d, 0.02);
    }

    return b.group;
}

function footprint(w, d) {
    const group = new THREE.Group();
    group.userData.noPick = true;
    const fill = new THREE.Mesh(
        new THREE.PlaneGeometry(w + 0.04, d + 0.04),
        new THREE.MeshBasicMaterial({ color: '#c47a3c', transparent: true, opacity: 0.18, depthWrite: false })
    );
    fill.rotation.x = -Math.PI / 2;
    fill.position.y = 0.006;
    fill.userData.noPick = true;
    group.add(fill);
    const edge = new THREE.LineSegments(
        new THREE.EdgesGeometry(new THREE.PlaneGeometry(w + 0.04, d + 0.04)),
        new THREE.LineBasicMaterial({ color: '#c47a3c' })
    );
    edge.rotation.x = -Math.PI / 2;
    edge.position.y = 0.008;
    edge.userData.noPick = true;
    group.add(edge);
    group.visible = false;
    return { group, fill, edge };
}

export class RoomStage {
    constructor(container, { label = null, onSelect = () => {}, onMove = () => {}, onDragEnd = () => {} } = {}) {
        this.container = container;
        this.label = label;
        this.onSelect = onSelect;
        this.onMove = onMove;
        this.onDragEnd = onDragEnd;
        this.room = { width: 5, depth: 4, height: 2.8, floor: 'oak', wall: '#f5f0e8' };
        this.items = new Map();
        this.selected = null;
        this.conflicts = new Set();
        this.view = '3d';
        this.cameraGoal = null;

        const width = container.clientWidth || 800;
        const height = container.clientHeight || 520;

        this.renderer = new THREE.WebGLRenderer({ antialias: true, preserveDrawingBuffer: true });
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
        this.renderer.setSize(width, height);
        this.renderer.shadowMap.enabled = true;
        this.renderer.shadowMap.type = THREE.PCFShadowMap;
        this.renderer.toneMapping = THREE.ACESFilmicToneMapping;
        this.renderer.toneMappingExposure = 1.05;
        container.appendChild(this.renderer.domElement);
        this.renderer.domElement.style.display = 'block';
        this.renderer.domElement.style.touchAction = 'none';

        this.scene = new THREE.Scene();
        this.scene.background = new THREE.Color('#eee7dc');

        this.camera = new THREE.PerspectiveCamera(38, width / height, 0.05, 200);
        this.controls = new OrbitControls(this.camera, this.renderer.domElement);
        this.controls.enableDamping = true;
        this.controls.dampingFactor = 0.08;
        this.controls.maxPolarAngle = Math.PI / 2 - 0.08;
        this.controls.minDistance = 1.5;
        this.controls.maxDistance = 22;
        this.controls.screenSpacePanning = false;

        this.scene.add(new THREE.HemisphereLight('#fffaf1', '#b8a58d', 1.25));
        this.sun = new THREE.DirectionalLight('#fff1dc', 2.1);
        this.sun.castShadow = true;
        this.sun.shadow.mapSize.set(2048, 2048);
        this.sun.shadow.bias = -0.0004;
        this.sun.shadow.normalBias = 0.02;
        this.sun.shadow.radius = 4;
        this.scene.add(this.sun);
        this.scene.add(this.sun.target);

        this.roomGroup = new THREE.Group();
        this.itemGroup = new THREE.Group();
        this.guideGroup = new THREE.Group();
        this.scene.add(this.roomGroup, this.itemGroup, this.guideGroup);

        this.raycaster = new THREE.Raycaster();
        this.pointer = new THREE.Vector2();
        this.floorPlane = new THREE.Plane(new THREE.Vector3(0, 1, 0), 0);
        this.drag = null;

        this.buildRoom();
        this.frame3d(true);
        this.bindPointer();

        this.resizeObserver = new ResizeObserver(() => this.resize());
        this.resizeObserver.observe(container);

        const loop = () => {
            this.raf = requestAnimationFrame(loop);
            this.tick();
        };
        loop();
    }

    resize() {
        const w = this.container.clientWidth;
        const h = this.container.clientHeight;
        if (!w || !h) return;
        this.camera.aspect = w / h;
        this.camera.updateProjectionMatrix();
        this.renderer.setSize(w, h);
    }

    tick() {
        if (this.cameraGoal) {
            const { position, target } = this.cameraGoal;
            this.camera.position.lerp(position, 0.14);
            this.controls.target.lerp(target, 0.14);
            if (this.camera.position.distanceTo(position) < 0.01) {
                this.camera.position.copy(position);
                this.controls.target.copy(target);
                this.cameraGoal = null;
            }
        }
        this.controls.update();
        this.renderer.render(this.scene, this.camera);
        this.placeLabel();
    }

    placeLabel() {
        if (!this.label) return;
        const entry = this.selected ? this.items.get(this.selected) : null;
        if (!entry) {
            this.label.style.opacity = '0';
            return;
        }
        const point = new THREE.Vector3(entry.root.position.x, entry.root.position.y + entry.data.box.h / 100 + 0.12, entry.root.position.z);
        point.project(this.camera);
        if (point.z > 1) {
            this.label.style.opacity = '0';
            return;
        }
        const x = (point.x * 0.5 + 0.5) * this.container.clientWidth;
        const y = (-point.y * 0.5 + 0.5) * this.container.clientHeight;
        this.label.style.opacity = '1';
        this.label.style.transform = `translate(${x}px, ${y}px) translate(-50%, -100%)`;
    }

    setRoom(room) {
        this.room = { ...this.room, ...room };
        this.buildRoom();
        this.items.forEach((entry) => this.positionEntry(entry));
        this.updateGuides();
        if (this.view === 'top') this.frameTop();
        else this.frame3d();
    }

    buildRoom() {
        this.roomGroup.clear();
        const { width: W, depth: D, height: H, floor, wall } = this.room;

        const base = new THREE.Mesh(new THREE.BoxGeometry(W + 0.3, 0.12, D + 0.3), material('#d8cdbd', { roughness: 0.9 }));
        base.position.set(0.05, -0.06, 0.05);
        base.receiveShadow = true;
        this.roomGroup.add(base);

        const texture = floorTexture(floor);
        texture.repeat.set(W / 2, D / 2);
        const floorMesh = new THREE.Mesh(new THREE.PlaneGeometry(W, D), new THREE.MeshStandardMaterial({ map: texture, roughness: floor === 'stone' ? 0.45 : 0.62 }));
        floorMesh.rotation.x = -Math.PI / 2;
        floorMesh.position.y = 0.001;
        floorMesh.receiveShadow = true;
        this.roomGroup.add(floorMesh);

        const wallMat = material(wall, { roughness: 0.95 });
        const thick = 0.12;
        const back = new THREE.Mesh(new THREE.BoxGeometry(W + thick, H, thick), wallMat);
        back.position.set(-thick / 2, H / 2, -D / 2 - thick / 2);
        back.receiveShadow = true;
        back.castShadow = true;
        this.roomGroup.add(back);
        const left = new THREE.Mesh(new THREE.BoxGeometry(thick, H, D), wallMat);
        left.position.set(-W / 2 - thick / 2, H / 2, 0);
        left.receiveShadow = true;
        left.castShadow = true;
        this.roomGroup.add(left);

        const skirtMat = material(shade(wall, -0.18), { roughness: 0.6 });
        const skirtBack = new THREE.Mesh(new THREE.BoxGeometry(W, 0.09, 0.015), skirtMat);
        skirtBack.position.set(0, 0.045, -D / 2 + 0.0075);
        this.roomGroup.add(skirtBack);
        const skirtLeft = new THREE.Mesh(new THREE.BoxGeometry(0.015, 0.09, D), skirtMat);
        skirtLeft.position.set(-W / 2 + 0.0075, 0.045, 0);
        this.roomGroup.add(skirtLeft);

        const winW = Math.min(1.6, W * 0.32);
        const winH = Math.min(1.4, H * 0.5);
        const winX = W * 0.18;
        const winY = H * 0.52;
        const frameMat = material('#ffffff', { roughness: 0.4 });
        const glass = new THREE.Mesh(new THREE.PlaneGeometry(winW, winH), new THREE.MeshStandardMaterial({ color: '#cfe3ec', emissive: '#dcecf3', emissiveIntensity: 0.55, roughness: 0.1 }));
        glass.position.set(winX, winY, -D / 2 + 0.004);
        this.roomGroup.add(glass);
        [[0, winH / 2], [0, -winH / 2], [0, 0]].forEach(([dx, dy]) => {
            const bar = new THREE.Mesh(new THREE.BoxGeometry(winW + 0.06, 0.04, 0.03), frameMat);
            bar.position.set(winX + dx, winY + dy, -D / 2 + 0.015);
            this.roomGroup.add(bar);
        });
        [-winW / 2, 0, winW / 2].forEach((dx) => {
            const bar = new THREE.Mesh(new THREE.BoxGeometry(0.04, winH, 0.03), frameMat);
            bar.position.set(winX + dx, winY, -D / 2 + 0.015);
            this.roomGroup.add(bar);
        });

        const doorW = 0.9;
        const doorH = Math.min(2.1, H - 0.3);
        const doorZ = D / 2 - Math.min(0.9, D * 0.25);
        const door = new THREE.Mesh(new THREE.BoxGeometry(0.02, doorH, doorW), material('#c9b49a', { roughness: 0.6 }));
        door.position.set(-W / 2 + 0.012, doorH / 2, doorZ);
        this.roomGroup.add(door);
        const knob = new THREE.Mesh(new THREE.SphereGeometry(0.025, 12, 10), material(METAL, { metalness: 0.8, roughness: 0.3 }));
        knob.position.set(-W / 2 + 0.04, 1.0, doorZ + doorW / 2 - 0.1);
        this.roomGroup.add(knob);

        const grid = new THREE.GridHelper(Math.max(W, D), Math.round(Math.max(W, D) * 2), '#8c7b6c', '#8c7b6c');
        grid.material.transparent = true;
        grid.material.opacity = 0.12;
        grid.scale.set(W / Math.max(W, D), 1, D / Math.max(W, D));
        grid.position.y = 0.003;
        this.roomGroup.add(grid);

        const widthLabel = textSprite(`${W.toFixed(1)} m`, { height: 0.2 });
        widthLabel.position.set(0, 0.05, D / 2 + 0.35);
        this.roomGroup.add(widthLabel);
        const depthLabel = textSprite(`${D.toFixed(1)} m`, { height: 0.2 });
        depthLabel.position.set(W / 2 + 0.4, 0.05, 0);
        this.roomGroup.add(depthLabel);

        const span = Math.max(W, D);
        this.sun.position.set(W * 0.6, H * 2.6, D * 0.9);
        this.sun.target.position.set(0, 0, 0);
        const cam = this.sun.shadow.camera;
        cam.left = -span;
        cam.right = span;
        cam.top = span;
        cam.bottom = -span;
        cam.near = 0.5;
        cam.far = span * 6 + H * 4;
        cam.updateProjectionMatrix();
    }

    frame3d(jump = false) {
        const { width: W, depth: D } = this.room;
        const span = Math.max(W, D);
        const position = new THREE.Vector3(span * 0.95, span * 1.05, span * 1.35);
        const target = new THREE.Vector3(-W * 0.04, 0.35, -D * 0.06);
        this.view = '3d';
        this.controls.enableRotate = true;
        this.goTo(position, target, jump);
    }

    frameTop() {
        const { width: W, depth: D, height: H } = this.room;
        const aspect = this.camera.aspect || 1.6;
        const fov = THREE.MathUtils.degToRad(this.camera.fov);
        const fitH = (D + 1.4) / 2 / Math.tan(fov / 2);
        const fitW = (W + 1.4) / 2 / Math.tan(fov / 2) / aspect;
        const height = Math.max(fitH, fitW) + H;
        this.view = 'top';
        this.controls.enableRotate = false;
        this.goTo(new THREE.Vector3(0, height, 0.0001), new THREE.Vector3(0, 0, 0), false);
    }

    goTo(position, target, jump) {
        if (jump) {
            this.camera.position.copy(position);
            this.controls.target.copy(target);
            this.cameraGoal = null;
            this.controls.update();
            return;
        }
        this.cameraGoal = { position, target };
    }

    setView(view) {
        if (view === 'top') this.frameTop();
        else this.frame3d();
        this.items.forEach((entry) => {
            entry.name.visible = view === 'top';
        });
    }

    setItems(list) {
        const seen = new Set();
        list.forEach((data) => {
            seen.add(data.uid);
            const signature = JSON.stringify([data.shape, data.box, data.colors, data.cord]);
            let entry = this.items.get(data.uid);
            if (entry && entry.signature !== signature) {
                this.itemGroup.remove(entry.root);
                entry = null;
            }
            if (!entry) {
                entry = this.createEntry(data, signature);
                this.items.set(data.uid, entry);
                this.itemGroup.add(entry.root);
            }
            entry.data = data;
            this.positionEntry(entry);
        });
        [...this.items.keys()].forEach((uid) => {
            if (!seen.has(uid)) {
                this.itemGroup.remove(this.items.get(uid).root);
                this.items.delete(uid);
            }
        });
        if (this.selected && !this.items.has(this.selected)) this.selected = null;
        this.refreshHighlights();
        this.updateGuides();
    }

    createEntry(data, signature) {
        const w = data.box.l / 100;
        const d = data.box.w / 100;
        const h = data.box.h / 100;
        const root = new THREE.Group();
        root.userData.uid = data.uid;
        const model = buildFurniture(data.shape, w, d, h, data.colors, { cord: data.cord });
        model.traverse((child) => {
            child.userData.uid = data.uid;
        });
        root.add(model);
        const fp = footprint(w, d);
        fp.group.position.y = -data.elevation;
        root.add(fp.group);
        const name = textSprite(data.name, { height: Math.min(0.15, Math.max(0.09, Math.min(w, d) * 0.3)) });
        name.position.set(0, h + 0.1, 0);
        name.visible = this.view === 'top';
        root.add(name);
        return { root, model, fp, name, data, signature };
    }

    positionEntry(entry) {
        const { width: W, depth: D } = this.room;
        const data = entry.data;
        entry.root.position.set(data.x / 100 - W / 2, data.elevation, data.y / 100 - D / 2);
        entry.root.rotation.y = -THREE.MathUtils.degToRad(data.rotation || 0);
        entry.fp.group.position.y = -data.elevation;
    }

    select(uid) {
        this.selected = uid && this.items.has(uid) ? uid : null;
        this.refreshHighlights();
        this.updateGuides();
    }

    setConflicts(uids) {
        this.conflicts = new Set(uids);
        this.refreshHighlights();
    }

    refreshHighlights() {
        this.items.forEach((entry, uid) => {
            const conflict = this.conflicts.has(uid);
            const selected = uid === this.selected;
            entry.fp.group.visible = conflict || selected;
            const color = conflict ? '#e11d48' : '#c47a3c';
            entry.fp.fill.material.color.set(color);
            entry.fp.fill.material.opacity = conflict ? 0.32 : 0.2;
            entry.fp.edge.material.color.set(color);
        });
    }

    updateGuides() {
        this.guideGroup.clear();
        const entry = this.selected ? this.items.get(this.selected) : null;
        if (!entry || entry.data.shape === 'pendant') return;
        const { width: W, depth: D } = this.room;
        const rotated = Math.round((entry.data.rotation || 0) / 90) % 2 !== 0;
        const fw = (rotated ? entry.data.box.w : entry.data.box.l) / 100;
        const fd = (rotated ? entry.data.box.l : entry.data.box.w) / 100;
        const cx = entry.root.position.x;
        const cz = entry.root.position.z;
        const y = 0.02;
        const segments = [
            [[-W / 2, cz], [cx - fw / 2, cz]],
            [[cx + fw / 2, cz], [W / 2, cz]],
            [[cx, -D / 2], [cx, cz - fd / 2]],
            [[cx, cz + fd / 2], [cx, D / 2]],
        ];
        const lineMat = new THREE.LineDashedMaterial({ color: '#7a5a3a', dashSize: 0.08, gapSize: 0.05, depthTest: false, transparent: true });
        segments.forEach(([[x1, z1], [x2, z2]]) => {
            const length = Math.hypot(x2 - x1, z2 - z1);
            if (length < 0.02) return;
            const line = new THREE.Line(new THREE.BufferGeometry().setFromPoints([new THREE.Vector3(x1, y, z1), new THREE.Vector3(x2, y, z2)]), lineMat);
            line.computeLineDistances();
            line.renderOrder = 15;
            this.guideGroup.add(line);
            const tag = textSprite(`${Math.round(length * 100)} cm`, { height: 0.13, background: 'rgba(122,90,58,0.92)', color: '#ffffff' });
            tag.position.set((x1 + x2) / 2, y + 0.06, (z1 + z2) / 2);
            this.guideGroup.add(tag);
        });
    }

    pick(event) {
        const rect = this.renderer.domElement.getBoundingClientRect();
        this.pointer.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
        this.pointer.y = -((event.clientY - rect.top) / rect.height) * 2 + 1;
        this.raycaster.setFromCamera(this.pointer, this.camera);
        const hits = this.raycaster.intersectObjects(this.itemGroup.children, true).filter((hit) => !hit.object.userData.noPick && hit.object.isMesh && hit.object.visible);
        if (!hits.length) return null;
        const prefer = hits.find((hit) => {
            const entry = this.items.get(hit.object.userData.uid);
            return entry && entry.data.shape !== 'rug';
        });
        return (prefer || hits[0]).object.userData.uid ?? null;
    }

    floorPoint(event) {
        const rect = this.renderer.domElement.getBoundingClientRect();
        this.pointer.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
        this.pointer.y = -((event.clientY - rect.top) / rect.height) * 2 + 1;
        this.raycaster.setFromCamera(this.pointer, this.camera);
        const point = new THREE.Vector3();
        return this.raycaster.ray.intersectPlane(this.floorPlane, point) ? point : null;
    }

    bindPointer() {
        const canvas = this.renderer.domElement;
        let down = null;

        canvas.addEventListener('pointerdown', (event) => {
            if (event.button !== 0) return;
            down = { x: event.clientX, y: event.clientY };
            const uid = this.pick(event);
            if (!uid) return;
            const entry = this.items.get(uid);
            const point = this.floorPoint(event);
            if (!entry || !point) return;
            this.select(uid);
            this.onSelect(uid);
            this.drag = { uid, dx: point.x - entry.root.position.x, dz: point.z - entry.root.position.z, moved: false };
            this.controls.enabled = false;
            canvas.setPointerCapture(event.pointerId);
            canvas.style.cursor = 'grabbing';
        });

        canvas.addEventListener('pointermove', (event) => {
            if (!this.drag) {
                if (event.buttons === 0) canvas.style.cursor = this.pick(event) ? 'grab' : 'default';
                return;
            }
            const point = this.floorPoint(event);
            const entry = this.items.get(this.drag.uid);
            if (!point || !entry) return;
            const { width: W, depth: D } = this.room;
            const x = Math.round((point.x - this.drag.dx + W / 2) * 100 / 5) * 5;
            const y = Math.round((point.z - this.drag.dz + D / 2) * 100 / 5) * 5;
            this.drag.moved = true;
            this.onMove(this.drag.uid, x, y);
        });

        const end = (event) => {
            if (this.drag) {
                const uid = this.drag.uid;
                const moved = this.drag.moved;
                this.drag = null;
                this.controls.enabled = true;
                canvas.style.cursor = 'grab';
                if (canvas.hasPointerCapture?.(event.pointerId)) canvas.releasePointerCapture(event.pointerId);
                if (moved) this.onDragEnd(uid);
                return;
            }
            if (down && Math.hypot(event.clientX - down.x, event.clientY - down.y) < 5 && event.type === 'pointerup') {
                this.select(null);
                this.onSelect(null);
            }
            down = null;
        };
        canvas.addEventListener('pointerup', end);
        canvas.addEventListener('pointercancel', end);
    }

    snapshot() {
        this.renderer.render(this.scene, this.camera);
        return this.renderer.domElement.toDataURL('image/png');
    }
}

window.MocanRoomStage = RoomStage;
window.dispatchEvent(new Event('mocan-room-stage'));
