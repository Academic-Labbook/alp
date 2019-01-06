const renderDivs = () => {
    const texblocks = document.querySelectorAll(".ssl-alp-tex");

    for (let i = 0; i < texblocks.length; i++) {
        const texblock = texblocks[i];
        const rendered = document.createElement("div");

        try {
            katex.render(
                texblock.textContent,
                rendered,
                {
                    displayMode: texblock.getAttribute("data-katex-display") === "true",
                    throwOnError: false
                }
            );
        } catch(e) {
            rendered.style.color = "red";
            rendered.textContent = e.message;
        }

        texblock.parentNode.replaceChild(rendered, texblock);
    }
}

const renderSpans = () => {
    const texspans = document.querySelectorAll(".ssl-alp-tex-inline");

    for (let i = 0; i < texspans.length; i++) {
        const texspan = texspans[i];
        const rendered = document.createElement("span");

        try {
            katex.render(
                texspan.textContent,
                rendered,
                {
                    displayMode: false,
                    throwOnError: false
                }
            );
        } catch(e) {
            rendered.style.color = "red";
            rendered.textContent = e.message;
        }

        texspan.parentNode.replaceChild(rendered, texspan);
    }
}

document.addEventListener("DOMContentLoaded", function() {
    renderDivs();
    renderSpans();
});
