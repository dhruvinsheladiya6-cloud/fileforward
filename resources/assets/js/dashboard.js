(function($) {

    "use strict";
    // Search
    let search = $(".search"),
        searchInput = $(".search .search-input input"),
        searchBtn = document.querySelector(".search-btn"),
        searchClose = document.querySelector(".search-close");

    if (searchBtn) {
        searchBtn.onclick = () => {
            search.addClass("active");
        };
    }
    if (searchClose) {
        searchClose.onclick = () => {
            search.removeClass("active");
            search.removeClass("show");
            searchInput.val("");
        };
    }

    // Dash Sidebar
    let dash = document.querySelector(".dash"),
        dashSidebarBtn = document.querySelector(".dash-sidebar-btn"),
        dashSidebarOverlay = document.querySelector((".dash-sidebar .overlay"));
    if (dash) {
        dashSidebarOverlay.onclick = dashSidebarBtn.onclick = () => {
            dash.classList.toggle("toggle");
        };
        window.addEventListener("resize", () => {
            dash.classList.remove("toggle");
        });
    }

    // File Check
    let files = document.querySelectorAll(".filemanager-file"),
        filesActions = document.querySelector(".filemanager-actions"),
        fileSelectAll = document.querySelector(".filemanager-select-all"),
        filesArray = [];
    if (files) {
        files.forEach((el) => {
            let fileCheckbox = el.querySelector(".form-check-input"),
                fileLinks = el.querySelectorAll(".filemanager-link"),
                fileDropdown = el.querySelector(".dropdown"),
                checkStat = false;

            function checkSelectedFiles() {
                let filesSelected = document.querySelectorAll(".filemanager-file.selected");
                if (filesSelected.length > 0) {
                    filesActions.classList.add("show");
                } else {
                    filesActions.classList.remove("show");
                }
                if (filesSelected.length === files.length) {
                    fileSelectAll.checked = true;
                    fileSelectAll.nextElementSibling.textContent = fileSelectAll.parentNode.getAttribute("data-unselect");
                } else {
                    fileSelectAll.checked = false;
                    fileSelectAll.nextElementSibling.textContent = fileSelectAll.parentNode.getAttribute("data-select");
                }
                files.forEach((ele) => {
                    if (ele.querySelector(".form-check-input").checked === true) {
                        filesArray.push(ele.querySelector(".form-check-input").id);
                        let uniquefilesArray = [...new Set(filesArray)];
                        filesSelectedInput.value = uniquefilesArray.sort();
                    } else {
                        filesArray = filesArray.filter(function(item) {
                            return item !== ele.querySelector(".form-check-input").id;
                        });
                        let uniquefilesArray = [...new Set(filesArray)];
                        filesSelectedInput.value = uniquefilesArray;
                    }
                });
            }
            fileCheckbox.onchange = () => {
                if (fileCheckbox.checked === true) {
                    el.classList.add("selected");
                    checkStat = fileCheckbox.checked;
                } else {
                    el.classList.remove("selected");
                    checkStat = fileCheckbox.checked;
                }
                checkSelectedFiles();
            };
            el.onclick = () => {
                if (fileCheckbox.checked === true) {
                    fileCheckbox.checked = false;
                    el.classList.remove("selected");
                    checkStat = fileCheckbox.checked;
                } else {
                    fileCheckbox.checked = true;
                    el.classList.add("selected");
                    checkStat = fileCheckbox.checked;
                }
                checkSelectedFiles();
            };
            fileDropdown.onclick = () => {
                if (fileCheckbox.checked === true) {
                    fileCheckbox.checked = false;
                    el.classList.remove("selected");
                    checkStat = fileCheckbox.checked;
                } else {
                    fileCheckbox.checked = true;
                    el.classList.add("selected");
                    checkStat = fileCheckbox.checked;
                }
            };
            fileCheckbox.onclick = (e) => {
                e.stopPropagation();
            };
            if (fileLinks) {
                fileLinks.forEach((link) => {
                    link.onclick = (e) => {
                        e.stopPropagation();
                    };
                });
            }
            fileSelectAll.onchange = () => {
                if (fileSelectAll.checked === true) {
                    files.forEach((el) => {
                        el.querySelector(".form-check-input").checked = true;
                        el.classList.add("selected");
                        checkStat = fileCheckbox.checked;
                    });
                } else {
                    files.forEach((el) => {
                        el.querySelector(".form-check-input").checked = false;
                        filesActions.classList.remove("show");
                        el.classList.remove("selected");
                        checkStat = fileCheckbox.checked;
                    });
                }
                checkSelectedFiles();
            };
        });
    }

    let avatarInput = $('#change_avatar'),
        targetedImagePreview = $('#avatar_preview');
    avatarInput.on('change', function() {
        var file = true,
            readLogoURL;
        if (file) {
            readLogoURL = function(input_file) {
                if (input_file.files && input_file.files[0]) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        targetedImagePreview.attr('src', e.target.result);
                    }
                    reader.readAsDataURL(input_file.files[0]);
                }
            }
        }
        readLogoURL(this);
    });

    let fileManagerShareFile = $('.fileManager-share-file'),
        shareModal = $('#shareModal'),
        shareModalSocialIcons = $('.share-modal .share'),
        shareModalPreviewLinkInput = $('.share-modal #copy-preview-link'),
        shareModalDownloadLinkInput = $('.share-modal #copy-download-link'),
        shareModalPreviewLink = $('.share-modal .preview-link'),
        shareModalFileName = $('.share-modal .filename');
    if (fileManagerShareFile.length) {
        fileManagerShareFile.on('click', function(e) {
            e.preventDefault();
            let share = $(this).data('share'),
                preview = $(this).data('preview'),
                facebook = "https://www.facebook.com/sharer/sharer.php?u=" + share.download_link,
                twitter = "https://twitter.com/intent/tweet?text=" + share.download_link,
                whatsapp = "https://wa.me/?text=" + share.download_link,
                linkedin = "https://www.linkedin.com/shareArticle?mini=true&url=" + share.download_link,
                pinterest = "http://pinterest.com/pin/create/button/?url=" + share.download_link;
            shareModalFileName.html('<strong>' + share.filename + '</strong>');
            shareModalSocialIcons.html('<a href="' + facebook + '" target="_blank" class="bg-facebook"><i class="fab fa-facebook-f"></i></a> <a href="' + twitter + '" target="_blank" class="bg-twitter"><i class="fab fa-twitter"></i></a> <a href="' + whatsapp + '" target="_blank" class="bg-whatsapp"><i class="fab fa-whatsapp"></i></a> <a href="' + linkedin + '" target="_blank" class="bg-linkedin"><i class="fab fa-linkedin"></i></a> <a href="' + pinterest + '" target="_blank" class="bg-pinterest"><i class="fab fa-pinterest"></i></a>');
            if (preview == true) {
                shareModalPreviewLink.removeClass('d-none');
                shareModalPreviewLinkInput.attr('value', share.preview_link);
            } else {
                shareModalPreviewLink.addClass('d-none');
            }
            shareModalDownloadLinkInput.attr('value', share.download_link);
            shareModal.modal('show');
        });
    }

})(jQuery);




