
function webuiBoot() {
    console.log("Webui is booting");
    corePoll();
    if (window.corepoll) {
        clearInterval(window.corepoll);
    }
    window.corepoll = setInterval(function () { corePoll(); }, 10000);
    window.debug = false;
    $("h3").on("dblclick", function () {
        $(".debugspan").removeClass("d-none");
        $(".debugbutton").removeClass("d-none");
        window.debug = true;
    });
    if (window.isloggedin) {
        console.log("Logged in");
        $("#logoutbutton").removeClass('d-none');
    }
}

function getModalElement() {
    return document.getElementById('coremodal');
}

function getModal() {
    if (!window.modal) {
        window.modal = new bootstrap.Modal(getModalElement());
    }
    return window.modal;
}

function setModalTitle(html) {
    $(".modal-title").html(html);
}
function setModalBody(html) {
    $(".modal-body").html(html);
}
function setModalYes(txt, callback) {
    $("#modalyes").off('click');
    if (!txt) {
        $("#modalyes").addClass("d-none").text("Nil");
    } else {
        $("#modalyes").removeClass("d-none");
        $("#modalyes").text(txt);
        $("#modalyes").on('click', function (e) {
            if (typeof callback == "function") {
                callback(e);
            }
            console.log(e);
            modalReset();
        });
    }
}
function setModalNo(txt) {
    if (!txt) {
        txt = "Cancel";
    }
    $("#modalno").text(txt);
}

function modalCancelCallback() {
    modalReset();
}
function modalReset() {
    modal = getModal();
    modal.hide();
    setModalTitle("No title");
    setModalBody("<p>No Modal Body</p>");
    setModalYes("Unknown");
}

function corePoll() {
    var url = "/core/api/poll";
    // console.log("Core was polled");
    $.get(url, function (d) { parsePollResp(d) })
}

function parsePollResp(data) {
    console.log(data);
    $(".currentver").text(data.runningdist.fullbuild);
    $(".kver").text(data.kernel.kver);
    $(".kbuild").text(data.kernel.kbuild);
    $(".latestver").text(data.latestdist.fullbuild);
    $("#uptime").text(data.uptime);
    $("#jsonresp>span").text(JSON.stringify(data));
    if (data.osupdateavail) {
        $("#noupdateavail").hide();
        $("#updateavail").show();
    } else {
        $("#noupdateavail").show();
        $("#updateavail").hide();
    }
    if (data.queuecount != 0) {
        $("#queuecount").text(data.queuecount + " jobs Queued");
    } else {
        $("#queuecount").text("");
    }
    $("#pkglist>li").remove();
    $.each(data.packages, function (i, v) {
        var h = "<li><tt class='larger pkgname'>" + i + "</tt> has local version <tt class='larger version'>"
            + v.local + "</tt>, remote version <tt class='larger version'>" + v.remote + "</tt>";
        if (v.update) {
            h += "<span class='updateneeded'>New version available</span>";
        }
        h += "</li>";
        $("#pkglist").append(h);
    });
    var d = new Date();
    $("#lastpoll").text(d.toString());
    $("#devstatus").text("Unavailable");
    genNetInfo(data.networkints);
    genSysInfo(data);
    if (typeof window.pollhook == "function") {
        console.log("Called window.pollhook");
        window.pollhook(data);
    }
}

function genSysInfo(data) {
    document.title = "SendFax.to " + data.systemid;
    $("#sysinfo>li").remove();
    $("#sysinfo").append("<li class='serialno'>Serial Number: " + data.serialno + " (" + data.systemid + ")</li>");
    $("#sysinfo").append("<li class='sysname'>System Name: " + data.systemname + "</li>");
    $.each(data.services, function (i, v) {
        $("#sysinfo").append("<li class='service'><span class='svcname'>" + i + ":</span> <span class='svcstatus'>" + v + "</span></li>");
    });
}

function genNetInfo(data) {
    $("#netif>li").remove();
    $.each(data, function (i, v) {
        var addrs = { "ipv4": {}, "ipv6": {} };
        var b = "<li><tt class='larger ifname'>" + i + "</tt> ";
        b += "<span class='macaddress'>" + v.address + "</span> "
        b += "<span class='operstate'>(" + v.operstate + ")</span> ";
        if (v.master) {
            b += "<br /><span class='bondparent'>Child interface of " + v.master + "</span> ";
        }
        $.each(v.addr_info, function (q, a) {
            if (a.scope == "host" || a.scope == "local" || a.scope == "link") {
                return;
            }
            if (a.family == "inet") {
                addrs.ipv4[a.local] = a;
            } else if (a.family == "inet6") {
                addrs.ipv6[a.local] = a;
            }
        })
        $.each(addrs, function (fam, d) {
            if (Object.keys(d).length) {
                b += "<br /><span class='intfam intfam" + fam + "'>";
                $.each(d, function (x, info) {
                    b += "<span class='ipaddr'>" + info.local + "/" + info.prefixlen + "</span> ";
                })
                b += "</span>";
                //  b += "with " + JSON.stringify(d) + "</span>";
            }
        });
        b += "</li > ";
        $("#netif").append(b);
    });
}

$(document).ready(function () { webuiBoot(); });