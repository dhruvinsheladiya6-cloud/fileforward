(function($) {

    "use strict";


    let dropzoneUploadBox = document.querySelector('.uploadbox'),
        dropzoneUploadBoxBtn = document.querySelectorAll('[data-upload-btn]');
    if (dropzoneUploadBox) {
        dropzoneUploadBoxBtn.forEach((el) => {
            el.onclick = () => {
                dropzoneUploadBox.classList.add('active');
                document.body.classList.add("overflow-hidden");
            };
        });
        dropzoneUploadBox.querySelector('.btn-close').onclick = () => {
            if (dropzone.getQueuedFiles().length > 0 || dropzone.getUploadingFiles().length > 0) {
                let removeConfirm = confirm(getUploadConfig.closeUploadBoxAlert);
                if (removeConfirm) {
                    dropzone.removeAllFiles(true);
                    dropzoneUploadBox.classList.remove("active");
                    document.body.classList.remove("overflow-hidden");
                }
            } else {
                dropzone.removeAllFiles(true);
                dropzoneUploadBox.classList.remove('active');
                document.body.classList.remove("overflow-hidden");
            }
        };
        let UploadUrl = getConfig.baseURL + '/upload';
        let previewNode = document.querySelector('#upload-previews');
        previewNode.id = "";
        let previewTemplate = previewNode.innerHTML;
        previewNode.parentNode.removeChild(previewNode);
        var dropzoneConfig = {
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                url: UploadUrl,
                method: 'POST',
                paramName: 'file',
                filesizeBase: 1024,
                maxFilesize: parseInt(getUploadConfig.maxFileSize),
                maxFiles: parseInt(getUploadConfig.maxUploadFiles),
                previewTemplate: previewTemplate,
                autoProcessQueue: false,
                clickable: document.querySelector(".uploadbox-drag .uploadbox-drag-inner"),
                parallelUploads: parseInt(getUploadConfig.maxUploadFiles),
                timeout: 0,
                chunking: true,
                forceChunking: true,
                chunkSize: parseInt(getUploadConfig.chunkSize),
                retryChunks: true,
                hiddenInputContainer: document.querySelector(".uploadbox-drag"),
            },
            dropzoneConfig = Object.assign({}, dropzoneConfig, dropzoneOptions);

        Dropzone.autoDiscover = false;
        var dropzone = new Dropzone("#dropzone", dropzoneConfig);
        dropzone.element.querySelector(".dz-message").remove();

        let dzHiddenInput = $('.dz-hidden-input');
        dzHiddenInput.attr('title', '');
        window.addEventListener("change", () => {
            $('.dz-hidden-input').attr('title', '');
        });

        let clickable = document.querySelectorAll('[data-dz-click]');
        clickable.forEach((el) => {
            el.onclick = (e) => {
                e.preventDefault();
                document.querySelector('.dz-hidden-input').click();
            };
        });

        let uploadMoreBtn = $('.upload-more-btn'),
            uploadboxWrapperForm = $('.uploadbox-wrapper-form'),
            submitBtn = $(".upload-files-btn"),
            uploadAutoDelete = $('.upload-auto-delete');
        submitBtn.on('click', function(e) {
            e.preventDefault();
            if (dropzone.files.length > 0) {
                if (getUploadConfig.filesDuration != "") {
                    let action = uploadAutoDelete.find(':selected').data('action');
                    if (action > getUploadConfig.filesDuration) {
                        toastr.error(getUploadConfig.filesDurationError);
                    } else {
                        submitBtn.prop('disabled', true);
                        uploadboxWrapperForm.addClass('d-none');
                        uploadMoreBtn.addClass('d-none');
                        dropzone.processQueue();
                    }
                } else {
                    submitBtn.prop('disabled', true);
                    uploadboxWrapperForm.addClass('d-none');
                    uploadMoreBtn.addClass('d-none');
                    dropzone.processQueue();
                }
            } else {
                toastr.error(getUploadConfig.nofilesAttachedError);
            }
        });

        let resetUploadBox = $('.reset-upload-box');
        resetUploadBox.on('click', function() {
            dropzone.removeAllFiles(true);
        });

        function uploadBoxDrag() {
            let uploadBox = $('.uploadbox'),
                uploadboxDrag = $('.uploadbox-drag'),
                uploadboxWrapper = $('.uploadbox-wrapper');
            if (dropzone.files.length > 0) {
                uploadboxDrag.addClass("inactive");
                uploadboxWrapper.addClass("active");
                $('body').addClass("overflow-hidden");
                resetUploadBox.removeClass("d-none");
                uploadBox.addClass("active");
                uploadMoreBtn.removeClass('d-none');
            } else {
                uploadboxDrag.removeClass("inactive");
                uploadboxWrapper.removeClass("active");
                resetUploadBox.addClass("d-none");
                submitBtn.prop('disabled', false);
                uploadboxWrapperForm.removeClass('d-none');
                uploadMoreBtn.addClass('d-none');
            }
        }

        function onAddFile(file) {
            if (getUploadConfig.subscribed != 0 && getUploadConfig.subscriptionExpired != 1 && getUploadConfig.subscriptionCanceled != 1) {
                if (dropzone.files.length <= getUploadConfig.maxUploadFiles) {
                    let unacceptableFileTypes = getUploadConfig.unacceptableFileTypes.split(','),
                        fileExtension = "." + fileExt(file.name);
                    if (!unacceptableFileTypes.includes(fileExtension)) {
                        if (this.files.length) {
                            var _i, _len;
                            for (_i = 0, _len = this.files.length; _i < _len - 1; _i++) {
                                if (this.files[_i].name === file.name) {
                                    this.removeFile(file);
                                    toastr.error(getUploadConfig.fileDuplicateError);
                                }
                            }
                        }
                        if (file.size == 0) {
                            toastr.error(getUploadConfig.emptyFilesError);
                            this.removeFile(file);
                        }
                        if (getUploadConfig.maxFileSize != "") {
                            if (file.size > getUploadConfig.maxFileSize) {
                                toastr.error(getUploadConfig.exceedTheAllowedSizeError);
                                this.removeFile(file);
                            }
                        }
                        if (getUploadConfig.clientReminingSpace != "") {
                            if (file.size > getUploadConfig.clientReminingSpace) {
                                toastr.error(getUploadConfig.clientReminingSpaceError);
                                this.removeFile(file);
                            }
                        }
                        uploadBoxDrag();
                        if (dropzone.files.length == getUploadConfig.maxUploadFiles) {
                            uploadMoreBtn.addClass('d-none');
                        }
                        let preview = $(file.previewElement),
                            fileEdit = preview.find('.dz-file-edit'),
                            fileEditBtn = preview.find("[data-dz-edit]"),
                            fileEditBtnIcon = preview.find(".dz-edit .fa"),
                            fileEditCLose = preview.find(".dz-file-edit-close"),
                            fileEditSubmit = preview.find(".dz-file-edit-submit"),
                            editPasswordInput = preview.find('.file-password');
                        editPasswordInput.on('input', function() {
                            editPasswordInput.removeClass('is-invalid');
                        });
                        fileEditBtn.on('click', function() {
                            if (editPasswordInput.val() != "") {
                                editPasswordInput.attr('fill-status', true);
                            }
                            editPasswordInput.prop('disabled', false);
                            fileEdit.addClass("active");
                        });
                        fileEditCLose.on('click', function() {
                            if (editPasswordInput.val() == "") {
                                editPasswordInput.prop('disabled', true);
                                editPasswordInput.attr('fill-status', false);
                                fileEditBtnIcon.removeClass('fa-lock');
                                fileEditBtnIcon.addClass('fa-lock-open');
                            } else {
                                if (editPasswordInput.attr('fill-status') == "false") {
                                    editPasswordInput.val("");
                                    editPasswordInput.prop('disabled', true);
                                    fileEditBtnIcon.removeClass('fa-lock');
                                    fileEditBtnIcon.addClass('fa-lock-open');
                                }
                            }
                            editPasswordInput.removeClass('is-invalid');
                            fileEdit.removeClass("active");
                        });
                        fileEditSubmit.on('click', function() {
                            if (editPasswordInput.val() == "") {
                                editPasswordInput.addClass('is-invalid');
                            } else {
                                fileEditBtnIcon.addClass('fa-lock');
                                fileEditBtnIcon.removeClass('fa-lock-open');
                                fileEdit.removeClass("active");
                            }
                        });
                        let previewFileName = preview.find("[data-dz-name]"),
                            previewFileSize = preview.find('.dz-size'),
                            previewFileExt = preview.find("[data-dz-extension]"),
                            fileExt = fileExtension.replace('.', '');
                        previewFileName.html(file.name);
                        previewFileSize.html(formatBytes(file.size));
                        if (fileExt != "") {
                            previewFileExt.attr('data-type', fileExt.substring(0, 4));
                        } else {
                            previewFileExt.attr('data-type', '?');
                        }
                        const containerPS = document.querySelectorAll('.dz-file-edit-box-body');
                        if (containerPS) {
                            containerPS.forEach((el) => {
                                new PerfectScrollbar(el);
                                el.classList.remove("ps--active-y");
                                el.classList.remove("ps--active-x");
                            });
                        }
                    } else {
                        this.removeFile(file);
                        toastr.error(getUploadConfig.unacceptableFileTypesError);
                    }
                } else {
                    this.removeFile(file);
                }
            } else {
                if (getUploadConfig.subscribed != 1) {
                    toastr.error(getUploadConfig.unsubscribedError);
                } else if (getUploadConfig.subscriptionExpired == 1) {
                    toastr.error(getUploadConfig.subscriptionExpiredError);
                } else if (getUploadConfig.subscriptionCanceled == 1) {
                    toastr.error(getUploadConfig.subscriptionCanceledError);
                }
                this.removeFile(file);
            }
        }

        function onSending(file, xhr, formData) {
            let preview = $(file.previewElement),
                fileRemoveBtn = preview.find('.dz-remove'),
                editDetailsIcon = preview.find('.dz-edit'),
                editDetailsModal = preview.find('.dz-file-edit'),
                password = preview.find('.file-password');
            formData.append('size', file.size);
            if (password.length) {
                formData.append('password', password.val());
            }
            formData.append('upload_auto_delete', $('.upload-auto-delete').val());
            fileRemoveBtn.remove();
            editDetailsIcon.remove();
            editDetailsModal.remove();
        }

        function onUploadProgress(file, progress, bytesSent) {
            let preview = $(file.previewElement);
            preview.find(".dz-upload-precent").html(progress.toFixed(0) + "%");
        }

        function onFileError(file, message = null) {
            toastr.error(message);
        }

        function onUploadComplete(file) {
            if (file.status == "success") {
                let preview = $(file.previewElement),
                    response = JSON.parse(file.xhr.response);
                if (response.type == 'success') {
                    let previewContainer = preview.find('.dz-preview-container');
                    if (response.preview_link != null) {
                        previewContainer.append('<div class="mt-3"><label class="form-label fw-500">' + getUploadConfig.translation.previewLink + '</label><div class="form-group"><input id="filebob' + response.preview_id + '" type="text" class="form-control form-control-md" value="' + response.preview_link + '" readonly> <button type="button" class="btn-copy" data-clipboard-target="#filebob' + response.preview_id + '"><i class="far fa-clone"></i></button></div></div>');
                    }
                    previewContainer.append('<div class="mt-3"><label class="form-label fw-500">' + getUploadConfig.translation.downloadLink + '</label><div class="form-group"><input id="filebob' + response.download_id + '" type="text" class="form-control form-control-md" value="' + response.download_link + '" readonly> <button type="button" class="btn-copy" data-clipboard-target="#filebob' + response.download_id + '"><i class="far fa-clone"></i></button></div></div>');
                    previewContainer.append('<a href="' + response.download_link + '" target="_blank" class="btn btn-primary w-100 btn-md mt-3"><i class="fas fa-eye me-2"></i>' + getUploadConfig.translation.viewFile + '</a>');
                    clipboardJS();
                } else {
                    preview.removeClass('dz-success');
                    preview.addClass('dz-error');
                    toastr.error(response.msg);
                }
            }
        }

        function onRemovedfile() {
            uploadBoxDrag();
        }

        function formatBytes(bytes, decimals = 2) {
            if (bytes === 0) return "0 " + getUploadConfig.translation.formatSizes[0];
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = getUploadConfig.translation.formatSizes;
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        }

        function fileExt(fileName) {
            return fileName.split('.').pop();
        }

        function clipboardJS() {
            let uploadedFileClipboardBtn = document.querySelectorAll(".btn-copy");
            if (uploadedFileClipboardBtn) {
                uploadedFileClipboardBtn.forEach((el) => {
                    let uploadedFileClipboard = new ClipboardJS(el);
                    uploadedFileClipboard.on("success", () => {
                        toastr.success(getConfig.copiedToClipboardSuccess);
                    });
                });
            }
        }

        dropzone.on("addedfile", onAddFile);
        dropzone.on('sending', onSending);
        dropzone.on('uploadprogress', onUploadProgress);
        dropzone.on('error', onFileError);
        dropzone.on('complete', onUploadComplete);
        dropzone.on("removedfile", onRemovedfile);
    }
})(jQuery);