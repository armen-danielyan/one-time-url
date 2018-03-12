jQuery(function($) {

    $(document).ready(function () {

        var custom_uploader;

        $("#fileupload").click(function (e) {
            e.preventDefault();
            if (custom_uploader) {
                custom_uploader.open();
                return;
            }

            custom_uploader = wp.media.frames.file_frame = wp.media({
                title: "Choose File",
                button: {
                    text: "Choose File"
                },
                multiple: false
            });

            custom_uploader.on("select", function () {
                var attachment = custom_uploader.state().get('selection').first().toJSON();
                $.ajax({
                    url: url.ajax_url,
                    data: {
                        action: "create_OTU_meta",
                        fileId: attachment.id
                    },
                    type: "POST",
                    success: function (res) {
                        console.log(JSON.parse(res));
                        location.reload();
                    }
                })
            });
            custom_uploader.open();
        });

        $(".delete-OTU-meta").on("click", function (e) {
            e.preventDefault();
            var itemId = $(this).data("id");
            $.ajax({
                url: url.ajax_url,
                data: {
                    action: "delete_OTU_meta",
                    ID: itemId
                },
                type: "POST",
                success: function (res) {
                    console.log(JSON.parse(res));
                    location.reload();
                }
            })
        });

        $(".save-otu-item").on("click", function () {
            var itemId = $(this).data("id");
            var refererReq = $("#referer-required-" + itemId).is(':checked') ? 'yes' : 'no';
            console.log(refererReq);
            $.ajax({
                url: url.ajax_url,
                data: {
                    action: "save_OTU_item",
                    ID: itemId,
                    validDays: $("#valid-days-" + itemId).val(),
                    referer_required: refererReq,
                    http_referer: $("#link-referer-" + itemId).val()
                },
                type: "POST",
                success: function (res) {
                    console.log(JSON.parse(res));
                    location.reload();
                }
            })
        });

        $("#tab-settings").on("click", function () {
            if (!$(this).hasClass("nav-tab-active")) {
                $("#tab-settings-page").show();
                $("#tab-general-page").hide();
                $(this).addClass("nav-tab-active");
                $("#tab-general").removeClass("nav-tab-active");
            }
        });

        $("#tab-general").on("click", function () {
            if (!$(this).hasClass("nav-tab-active")) {
                $("#tab-settings-page").hide();
                $("#tab-general-page").show();
                $(this).addClass("nav-tab-active");
                $("#tab-settings").removeClass("nav-tab-active");
            }
        })
    })

});