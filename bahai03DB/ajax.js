function ajax(surl, svars){

    var xmlhttp = new XMLHttpRequest();
    xmlhttp.open("POST", surl, false);
    xmlhttp.setRequestHeader("Content-Type",
            "application/x-www-form-urlencoded"); 
    xmlhttp.send(svars);
    return xmlhttp.responseText;
}
