(function($) {

    "use strict";

    let contactForm = $('#contactForm'),
        sendMessageBtn = $('#sendMessage');
    if (contactForm.length) {
        sendMessageBtn.on('click', function(e) {
            e.preventDefault();
            let formData = contactForm.serializeArray(),
                sendUrl = getConfig.baseURL + '/contact-us/send';
            sendMessageBtn.prop('disabled', true);
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                url: sendUrl,
                type: "POST",
                data: formData,
                dataType: 'json',
                beforeSend: function() {
                    $('body').LoadingOverlay("show");
                },
            }).done(function(response) {
                $('body').LoadingOverlay("hide");
                sendMessageBtn.prop('disabled', false);
                if ($.isEmptyObject(response.error)) {
                    contactForm.trigger("reset");
                    if (window.grecaptcha) {
                        grecaptcha.reset();
                    }
                    toastr.success(response.success);
                } else {
                    toastr.error(response.error);
                }
            }).fail(function(jqXHR, ajaxOptions, thrownError) {
                $('body').LoadingOverlay("hide");
                toastr.error(thrownError);
            });
        });
    }

    let ctxUploads = $('#uploads-chart');
    if (ctxUploads.length) {
        const charts = {
            initUploads: function() { this.uploadsChartsData() },
            uploadsChartsData: function() {
                const dataUrl = getConfig.baseURL + '/user/dashboard/charts/uploads';
                const request = $.ajax({
                    method: 'GET',
                    url: dataUrl
                });
                request.done(function(response) {
                    charts.createUploadsCharts(response);
                });
            },
            createUploadsCharts: function(response) {
                const max = response.suggestedMax;
                const labels = response.uploadsChartLabels;
                const data = response.uploadsChartData;
                window.Chart && (new Chart(ctxUploads, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Uploads',
                            data: data,
                            fill: true,
                            tension: 0.3,
                            backgroundColor: getConfig.primaryColor,
                            borderColor: getConfig.primaryColor,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false,
                            }
                        },
                        scales: {
                            y: {
                                suggestedMax: max,
                            }
                        }
                    }
                })).render();
            },
        };
        charts.initUploads();
    }
})(jQuery);