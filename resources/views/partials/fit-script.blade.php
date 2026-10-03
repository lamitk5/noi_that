<script>
    function mocanParseDims(text) {
        if (!text) return null;
        const matches = String(text).match(/\d+(?:[.,]\d+)?/g);
        if (!matches || matches.length < 3) return null;
        const numbers = matches.slice(0, 3).map((value) => parseFloat(value.replace(',', '.')));
        if (Math.min(...numbers) <= 0) return null;
        return { l: numbers[0], w: numbers[1], h: numbers[2] };
    }

    function mocanFits(item, openingWidth, openingHeight) {
        const width = parseFloat(openingWidth);
        const height = parseFloat(openingHeight);
        if (!item || !(width > 0) || !(height > 0)) return null;
        const dims = [item.l, item.w, item.h];
        for (let depth = 0; depth < 3; depth++) {
            const face = dims.filter((_, index) => index !== depth);
            if ((face[0] <= width && face[1] <= height) || (face[1] <= width && face[0] <= height)) {
                return true;
            }
        }
        return false;
    }
</script>
