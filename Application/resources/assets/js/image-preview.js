(function($) {
    "use strict";
    const img = new Image();
    const imgFile = document.querySelector(".fileviewer-image img");
    let aspect, zoomPercent = .2,
        zoomMin = 150,
        rotate = false,
        rotateType;
    let viewerControlers = document.querySelector(".fileviewer-controler"),
        imgContainer = document.querySelector(".fileviewer-image");
    if (imgContainer) {
        rotateType = imgFile.className;
        img.src = imgFile.src;
        window.addEventListener("load", () => {
            aspect = img.naturalWidth / img.naturalHeight;
        });
        function imgDefaultRotate() {
            if (rotateType === "rp-90" || rotateType === "rp-270" || rotateType === "rm-90" || rotateType === "rm-270") {
                rotate = true;
                imgSizeRotate();
                imgRotateOp();
            } else {
                imgSizeOP();
            }
        }
        function imgResetPosition() {
            imgContainer.removeAttribute("style");
        }
        function imgResetClass() {
            imgFile.removeAttribute("class");
            imgFile.classList.add(rotateType);
            rotate = false;
            imgResetPosition();
        }
        function imgSizeOP() {
            let imgHeight = window.innerHeight - 120,
                imgWidth = imgHeight * aspect;
            if (window.innerWidth < 1921) {
                if (img.width > window.innerWidth - 120 || img.height > window.innerHeight - 120) {
                    if (imgWidth > window.innerWidth) {
                        imgFile.style.height = ((window.innerWidth - 32) / aspect) + "px";
                        imgFile.style.width = window.innerWidth - 32 + "px";
                    } else {
                        imgFile.style.height = imgHeight + "px";
                        imgFile.style.width = imgWidth + "px";
                    }
                } else {
                    imgFile.style.height = img.height + "px";
                    imgFile.style.width = img.width + "px";
                }
            } else {
                if (img.width > window.innerWidth - 120 || img.height > window.innerHeight - 120) {
                    imgFile.style.height = window.innerHeight - 120 + "px";
                    imgFile.style.width = (window.innerHeight - 120) * aspect + "px";
                } else {
                    imgFile.style.height = img.height + "px";
                    imgFile.style.width = img.width + "px";
                }
            }
        }
        function imgSizeRotate() {
            let imgWidth = window.innerHeight - 120,
                imgHeight = imgWidth / aspect;
            if (window.innerWidth < 1921) {
                if (img.width > window.innerHeight - 120 || img.height > window.innerWidth - 120) {
                    if (imgWidth > window.innerWidth) {
                        imgFile.style.height = ((window.innerWidth - 32) / aspect) + "px";
                        imgFile.style.width = window.innerWidth - 32 + "px";
                    } else {
                        imgFile.style.height = imgHeight + "px";
                        imgFile.style.width = imgWidth + "px";
                    }
                } else {
                    imgFile.style.height = img.height + "px";
                    imgFile.style.width = img.width + "px";
                }
            } else {
                if (img.width > window.innerHeight - 120 || img.height > window.innerWidth - 120) {
                    imgFile.style.height = (window.innerHeight - 120) / aspect + "px";
                    imgFile.style.width = window.innerHeight - 120 + "px";
                } else {
                    imgFile.style.height = img.width / aspect + "px";
                    imgFile.style.width = img.width + "px";
                }
            }
        }
        function imgRotateOp() {
            if (rotate === true) {
                imgContainer.style.position = "relative";
                imgContainer.style.left = 0;
                imgContainer.style.width = imgFile.height + "px";
                imgContainer.style.height = imgFile.width + "px";
                imgContainer.style.overflow = "hidden";
                if (imgContainer.offsetLeft < 0) {
                    let imgContainerLeft = imgContainer.offsetLeft * -1;
                    imgContainer.style.left = imgContainerLeft + "px";
                }
            } else {
                imgContainer.style.position = "relative";
                imgContainer.style.left = 0;
                if (imgContainer.offsetLeft < 0) {
                    let imgContainerLeft = imgContainer.offsetLeft * -1;
                    imgContainer.style.left = imgContainerLeft + "px";
                }
            }
        }
        function ZoomIn() {
            if (imgFile.width <= img.width - (imgFile.width - (imgFile.width * zoomPercent)) || imgFile.height <= img.height - (imgFile.height - (imgFile.height * zoomPercent))) {
                imgFile.style.height = imgFile.height * (zoomPercent + 1) + "px";
                imgFile.style.width = imgFile.width * (zoomPercent + 1) + "px";
            } else {
                imgFile.style.height = (img.width / aspect) + "px";
                imgFile.style.width = img.width + "px";
            }
            imgRotateOp();
        }
        function ZoomOut() {
            if (imgFile.width >= zoomMin && imgFile.height >= zoomMin) {
                imgFile.style.height = imgFile.height / (zoomPercent + 1) + "px";
                imgFile.style.width = imgFile.width / (zoomPercent + 1) + "px";
                imgResetPosition();
            }
            imgRotateOp();
        }
        function imgScroll() {
            if (rotate === true) {
                if (imgFile.width > window.innerHeight && imgFile.height > window.innerWidth) {
                    document.body.classList.add("overflow-x-auto");
                    document.body.classList.add("overflow-y-auto");
                } else if (imgFile.height > window.innerWidth) {
                    document.body.classList.add("overflow-x-auto");
                    document.body.classList.remove("overflow-y-auto");
                } else if (imgFile.width > window.innerHeight) {
                    document.body.classList.add("overflow-y-auto");
                    document.body.classList.remove("overflow-x-auto");
                }
            } else {
                if (imgFile.width > window.innerWidth && imgFile.height > window.innerHeight) {
                    document.body.classList.add("overflow-x-auto");
                    document.body.classList.add("overflow-y-auto");
                } else if (imgFile.width > window.innerWidth) {
                    document.body.classList.add("overflow-x-auto");
                    document.body.classList.remove("overflow-y-auto");
                } else if (imgFile.height > window.innerHeight) {
                    document.body.classList.add("overflow-y-auto");
                    document.body.classList.remove("overflow-x-auto");
                }
            }
        }
        function rotateRight() {
            imgResetPosition();
            if (imgFile.classList.contains("r-0")) {
                imgFile.removeAttribute("class");
                imgFile.classList.add("rp-90");
                rotate = true;
                imgSizeRotate();
                imgRotateOp();
            } else if (imgFile.classList.contains("rp-90")) {
                imgFile.removeAttribute("class");
                imgFile.classList.add("rp-180");
                rotate = false;
                imgSizeOP();
            } else if (imgFile.classList.contains("rp-180")) {
                imgFile.removeAttribute("class");
                imgFile.classList.add("rp-270");
                rotate = true;
                imgSizeRotate();
                imgRotateOp();
            } else if (imgFile.classList.contains("rp-270")) {
                imgFile.removeAttribute("class");
                imgFile.classList.add("r-0");
                rotate = false;
                imgSizeOP();
            } else if (imgFile.classList.contains("rm-90")) {
                imgFile.removeAttribute("class");
                imgFile.classList.add("r-0");
                rotate = false;
                imgSizeOP();
            } else if (imgFile.classList.contains("rm-180")) {
                imgFile.removeAttribute("class");
                imgFile.classList.add("rm-90");
                rotate = true;
                imgSizeRotate();
                imgRotateOp();
            } else if (imgFile.classList.contains("rm-270")) {
                imgFile.removeAttribute("class");
                imgFile.classList.add("rm-180");
                rotate = false;
                imgSizeOP();
            }
        }

        function rotateLeft() {
            imgResetPosition();
            if (imgFile.classList.contains("r-0")) {
                imgFile.removeAttribute("class");
                imgFile.classList.add("rm-90");
                rotate = true;
                imgSizeRotate();
                imgRotateOp();
            } else if (imgFile.classList.contains("rm-90")) {
                imgFile.removeAttribute("class");
                imgFile.classList.add("rm-180");
                rotate = false;
                imgSizeOP();
            } else if (imgFile.classList.contains("rm-180")) {
                imgFile.removeAttribute("class");
                imgFile.classList.add("rm-270");
                rotate = true;
                imgSizeRotate();
                imgRotateOp();
            } else if (imgFile.classList.contains("rm-270")) {
                imgFile.removeAttribute("class");
                imgFile.classList.add("r-0");
                rotate = false;
                imgSizeOP();
            } else if (imgFile.classList.contains("rp-90")) {
                imgFile.removeAttribute("class");
                imgFile.classList.add("r-0");
                rotate = false;
                imgSizeOP();
            } else if (imgFile.classList.contains("rp-180")) {
                imgFile.removeAttribute("class");
                imgFile.classList.add("rp-90");
                rotate = true;
                imgSizeRotate();
                imgRotateOp();
            } else if (imgFile.classList.contains("rp-270")) {
                imgFile.removeAttribute("class");
                imgFile.classList.add("rp-180");
                rotate = false;
                imgSizeOP();
            }
        }
        let rotateL = viewerControlers.querySelector(".rotate-left"),
            rotateR = viewerControlers.querySelector(".rotate-right"),
            zoomInBtn = viewerControlers.querySelector(".zoom-in"),
            zoomOutBtn = viewerControlers.querySelector(".zoom-out");
        imgSizeOP();
        rotateL.addEventListener("click", rotateLeft);
        rotateR.addEventListener("click", rotateRight);
        zoomInBtn.addEventListener("click", ZoomIn);
        zoomOutBtn.addEventListener("click", ZoomOut);
        window.addEventListener("load", imgDefaultRotate);
        window.addEventListener("click", imgScroll);
        window.addEventListener("resize", imgSizeOP);
        window.addEventListener("resize", imgResetClass);
        window.addEventListener("resize", imgDefaultRotate);
        imgSizeRotate();
    }
    let viewerFile = document.querySelector(".fileviewer-file"),
        contextMenu = document.querySelector(".contextmenu");
    if (viewerFile) {
        viewerFile.oncontextmenu = (e) => {
            e.preventDefault();
            contextMenu.classList.add("show");
            if (window.innerWidth - e.clientX < contextMenu.clientWidth && window.innerHeight - e.clientY < contextMenu.clientHeight) {
                contextMenu.removeAttribute("style");
                contextMenu.style.right = window.innerWidth - e.clientX + "px";
                contextMenu.style.bottom = window.innerHeight - e.clientY + "px";
            } else if (window.innerWidth - e.clientX < contextMenu.clientWidth) {
                contextMenu.removeAttribute("style");
                contextMenu.style.top = e.clientY + "px";
                contextMenu.style.right = window.innerWidth - e.clientX + "px";
            } else if (window.innerHeight - e.clientY < contextMenu.clientHeight) {
                contextMenu.removeAttribute("style");
                contextMenu.style.bottom = window.innerHeight - e.clientY + "px";
                contextMenu.style.left = e.clientX + "px";
            } else {
                contextMenu.removeAttribute("style");
                contextMenu.style.top = e.clientY + "px";
                contextMenu.style.left = e.clientX + "px";
            }
        };
        window.addEventListener("click", () => {
            contextMenu.classList.remove("show");
        });
        contextMenu.querySelectorAll(".contextmenu-item").forEach((el) => {
            el.oncontextmenu = (e) => {
                e.preventDefault();
            };
        });
    }
})(jQuery);