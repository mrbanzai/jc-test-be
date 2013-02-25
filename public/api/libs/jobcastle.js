/**
 * The JobCastle JavaScript client API.
 */
function Jobcastle(publicKey, privateKey, apiBaseUrl) {

    // private vars
    var self = this;

    // public vars
    this.apiBaseUrl = 'http://api.jobcastle.com/api/';
    this.version = '1.0';
    this.publicKey = null;
    this.privateKey = null;

    if (publicKey) { self.publicKey = publicKey; }
    if (privateKey) { self.privateKey = privateKey; }
    if (apiBaseUrl) { self.apiBaseUrl = apiBaseUrl; }

    /**
     * Request handler for GET (entry retrieval) calls.
     */
    this.get = function(resource, params, callbackFcn) {
        if ($.isFunction(params) && !callbackFcn) {
            callbackFcn = params;
            params = null;
        }
        self.request(resource, params, 'GET', callbackFcn);
    }

    /**
     * Request handler for POST (create) calls.
     */
    this.post = function(resource, params, callbackFcn) {
        if ($.isFunction(params) && !callbackFcn) {
            callbackFcn = params;
            params = null;
        }
        self.request(resource, params, 'POST', callbackFcn);
    }

    /**
     * Request handler for PUT (update) calls.
     */
    this.put = function(resource, params, callbackFcn) {
        if ($.isFunction(params) && !callbackFcn) {
            callbackFcn = params;
            params = null;
        }
        self.request(resource, params, 'PUT', callbackFcn);
    }

    /**
     * Request handler for DELETE (entry removal) calls.
     */
    this.del = function(resource, params, callbackFcn) {
        if ($.isFunction(params) && !callbackFcn) {
            callbackFcn = params;
            params = null;
        }
        self.request(resource, params, 'DELETE', callbackFcn);
    }

    /**
     * Request handler.
     */
    this.request = function(resource, params, method, callbackFcn) {
        if (typeof callbackFcn != 'function') {
            alert('You must provide a valid callback function.');
            return false;
        }

        // generate the request URL
        var url = self.apiBaseUrl.replace(/\/$/g, ''); //+ self.version;
        url += resource.replace(/\/$/g, ''); // + '/&callback=?';

        // add any params
        params = _prepareParams(params);

        // fire off request
        $.ajax({
            url: url,
            type: method,
            data: params,
            dataType: 'jsonp',
            success: function(data, textStatus, jqXHR) {
                callbackFcn(data, textStatus, jqXHR);
            },
            error: function(jqXHR, textStatus, errorThrown) {
                if (window.console) {
                    console.log('An error occurred attempting to make the API request.');
                    console.log(textStatus);
                    console.log(errorThrown);
                }
            },
            beforeSend: function(req) {
                var userAgent = location.host + ' - JavaScript Client v' + self.version;
                req.setRequestHeader('User-Agent', userAgent);
            }
        });
    }

    /**
     * Prepare request parameters.
     */
    function _prepareParams(params) {
        var ref = window.location.hostname;
        // convert all params to UTF-8
        if (!params) {
            params = {};
        }
        params.referrer = ref;
        params = toUtf8(params);

        // convert params to JSON string
        var data = JSON.stringify(params);
        if (data === 'null') {
            data = {};
        }
        // convert data to byte encoding
        var hmacBytes = Crypto.HMAC(Crypto.SHA1, data, self.privateKey, { asBytes: false });
        console.log('data: ' + data);
        console.log('bytes: ' + hmacBytes);
        // generate signature
        var sig = base64_encode(hmacBytes);
        // return the prepared data
        return {
            'data': data,
            'sig': sig,
            'publicKey': self.publicKey
        };
    }

    /****************************************
     ****************************************
     * Helper libraries below. Don't touch. *
     ****************************************
     ****************************************/

    /**
     * JSON parsing
     */
    if(!this.JSON) this.JSON={};
    (function(){function k(a){return a<10?"0"+a:a}function o(a){p.lastIndex=0;return p.test(a)?'"'+a.replace(p,function(a){var c=r[a];return typeof c==="string"?c:"\\u"+("0000"+a.charCodeAt(0).toString(16)).slice(-4)})+'"':'"'+a+'"'}function l(a,i){var c,d,h,m,g=e,f,b=i[a];b&&typeof b==="object"&&typeof b.toJSON==="function"&&(b=b.toJSON(a));typeof j==="function"&&(b=j.call(i,a,b));switch(typeof b){case "string":return o(b);case "number":return isFinite(b)?String(b):"null";case "boolean":case "null":return String(b);
    case "object":if(!b)return"null";e+=n;f=[];if(Object.prototype.toString.apply(b)==="[object Array]"){m=b.length;for(c=0;c<m;c+=1)f[c]=l(c,b)||"null";h=f.length===0?"[]":e?"[\n"+e+f.join(",\n"+e)+"\n"+g+"]":"["+f.join(",")+"]";e=g;return h}if(j&&typeof j==="object"){m=j.length;for(c=0;c<m;c+=1)d=j[c],typeof d==="string"&&(h=l(d,b))&&f.push(o(d)+(e?": ":":")+h)}else for(d in b)Object.hasOwnProperty.call(b,d)&&(h=l(d,b))&&f.push(o(d)+(e?": ":":")+h);h=f.length===0?"{}":e?"{\n"+e+f.join(",\n"+e)+"\n"+
    g+"}":"{"+f.join(",")+"}";e=g;return h}}if(typeof Date.prototype.toJSON!=="function")Date.prototype.toJSON=function(){return isFinite(this.valueOf())?this.getUTCFullYear()+"-"+k(this.getUTCMonth()+1)+"-"+k(this.getUTCDate())+"T"+k(this.getUTCHours())+":"+k(this.getUTCMinutes())+":"+k(this.getUTCSeconds())+"Z":null},String.prototype.toJSON=Number.prototype.toJSON=Boolean.prototype.toJSON=function(){return this.valueOf()};var q=/[\u0000\u00ad\u0600-\u0604\u070f\u17b4\u17b5\u200c-\u200f\u2028-\u202f\u2060-\u206f\ufeff\ufff0-\uffff]/g,
    p=/[\\\"\x00-\x1f\x7f-\x9f\u00ad\u0600-\u0604\u070f\u17b4\u17b5\u200c-\u200f\u2028-\u202f\u2060-\u206f\ufeff\ufff0-\uffff]/g,e,n,r={"\u0008":"\\b","\t":"\\t","\n":"\\n","\u000c":"\\f","\r":"\\r",'"':'\\"',"\\":"\\\\"},j;if(typeof JSON.stringify!=="function")JSON.stringify=function(a,i,c){var d;n=e="";if(typeof c==="number")for(d=0;d<c;d+=1)n+=" ";else typeof c==="string"&&(n=c);if((j=i)&&typeof i!=="function"&&(typeof i!=="object"||typeof i.length!=="number"))throw Error("JSON.stringify");return l("",
    {"":a})};if(typeof JSON.parse!=="function")JSON.parse=function(a,e){function c(a,d){var g,f,b=a[d];if(b&&typeof b==="object")for(g in b)Object.hasOwnProperty.call(b,g)&&(f=c(b,g),f!==void 0?b[g]=f:delete b[g]);return e.call(a,d,b)}var d;a=String(a);q.lastIndex=0;q.test(a)&&(a=a.replace(q,function(a){return"\\u"+("0000"+a.charCodeAt(0).toString(16)).slice(-4)}));if(/^[\],:{}\s]*$/.test(a.replace(/\\(?:["\\\/bfnrt]|u[0-9a-fA-F]{4})/g,"@").replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g,
    "]").replace(/(?:^|:|,)(?:\s*\[)+/g,"")))return d=eval("("+a+")"),typeof e==="function"?c({"":d},""):d;throw new SyntaxError("JSON.parse");}})();

    /*
     * Crypto-JS v2.0.0
     * http://code.google.com/p/crypto-js/
     * Copyright (c) 2009, Jeff Mott. All rights reserved.
     * http://code.google.com/p/crypto-js/wiki/License
     */
    (function(){var c="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";var d=window.Crypto={};var a=d.util={rotl:function(h,g){return(h<<g)|(h>>>(32-g))},rotr:function(h,g){return(h<<(32-g))|(h>>>g)},endian:function(h){if(h.constructor==Number){return a.rotl(h,8)&16711935|a.rotl(h,24)&4278255360}for(var g=0;g<h.length;g++){h[g]=a.endian(h[g])}return h},randomBytes:function(h){for(var g=[];h>0;h--){g.push(Math.floor(Math.random()*256))}return g},bytesToWords:function(h){for(var k=[],j=0,g=0;j<h.length;j++,g+=8){k[g>>>5]|=h[j]<<(24-g%32)}return k},wordsToBytes:function(i){for(var h=[],g=0;g<i.length*32;g+=8){h.push((i[g>>>5]>>>(24-g%32))&255)}return h},bytesToHex:function(g){for(var j=[],h=0;h<g.length;h++){j.push((g[h]>>>4).toString(16));j.push((g[h]&15).toString(16))}return j.join("")},hexToBytes:function(h){for(var g=[],i=0;i<h.length;i+=2){g.push(parseInt(h.substr(i,2),16))}return g},bytesToBase64:function(h){if(typeof btoa=="function"){return btoa(e.bytesToString(h))}for(var g=[],l=0;l<h.length;l+=3){var m=(h[l]<<16)|(h[l+1]<<8)|h[l+2];for(var k=0;k<4;k++){if(l*8+k*6<=h.length*8){g.push(c.charAt((m>>>6*(3-k))&63))}else{g.push("=")}}}return g.join("")},base64ToBytes:function(h){if(typeof atob=="function"){return e.stringToBytes(atob(h))}h=h.replace(/[^A-Z0-9+\/]/ig,"");for(var g=[],j=0,k=0;j<h.length;k=++j%4){if(k==0){continue}g.push(((c.indexOf(h.charAt(j-1))&(Math.pow(2,-2*k+8)-1))<<(k*2))|(c.indexOf(h.charAt(j))>>>(6-k*2)))}return g}};d.mode={};var b=d.charenc={};var f=b.UTF8={stringToBytes:function(g){return e.stringToBytes(unescape(encodeURIComponent(g)))},bytesToString:function(g){return decodeURIComponent(escape(e.bytesToString(g)))}};var e=b.Binary={stringToBytes:function(j){for(var g=[],h=0;h<j.length;h++){g.push(j.charCodeAt(h))}return g},bytesToString:function(g){for(var j=[],h=0;h<g.length;h++){j.push(String.fromCharCode(g[h]))}return j.join("")}}})();(function(){var f=Crypto,a=f.util,b=f.charenc,e=b.UTF8,d=b.Binary;var c=f.SHA1=function(i,g){var h=a.wordsToBytes(c._sha1(i));return g&&g.asBytes?h:g&&g.asString?d.bytesToString(h):a.bytesToHex(h)};c._sha1=function(o){if(o.constructor==String){o=e.stringToBytes(o)}var v=a.bytesToWords(o),x=o.length*8,p=[],r=1732584193,q=-271733879,k=-1732584194,h=271733878,g=-1009589776;v[x>>5]|=128<<(24-x%32);v[((x+64>>>9)<<4)+15]=x;for(var z=0;z<v.length;z+=16){var E=r,D=q,C=k,B=h,A=g;for(var y=0;y<80;y++){if(y<16){p[y]=v[z+y]}else{var u=p[y-3]^p[y-8]^p[y-14]^p[y-16];p[y]=(u<<1)|(u>>>31)}var s=((r<<5)|(r>>>27))+g+(p[y]>>>0)+(y<20?(q&k|~q&h)+1518500249:y<40?(q^k^h)+1859775393:y<60?(q&k|q&h|k&h)-1894007588:(q^k^h)-899497514);g=h;h=k;k=(q<<30)|(q>>>2);q=r;r=s}r+=E;q+=D;k+=C;h+=B;g+=A}return[r,q,k,h,g]};c._blocksize=16})();
    (function(){var e=Crypto,a=e.util,b=e.charenc,d=b.UTF8,c=b.Binary;e.HMAC=function(l,m,k,h){if(m.constructor==String){m=d.stringToBytes(m)}if(k.constructor==String){k=d.stringToBytes(k)}if(k.length>l._blocksize*4){k=l(k,{asBytes:true})}var g=k.slice(0),n=k.slice(0);for(var j=0;j<l._blocksize*4;j++){g[j]^=92;n[j]^=54}var f=l(g.concat(l(n.concat(m),{asBytes:true})),{asBytes:true});return h&&h.asBytes?f:h&&h.asString?c.bytesToString(f):a.bytesToHex(f)}})();

    /**
     * Implementation of base64_encode.
     */
    function base64_encode(data) {
        var b64 = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
        var o1, o2, o3, h1, h2, h3, h4, bits, i = 0,
            ac = 0,
            enc = "",
            tmp_arr = [];
        if (!data) {
            return data;
        }
        data = utf8_encode(data + '');

        do {
            o1 = data.charCodeAt(i++);
            o2 = data.charCodeAt(i++);
            o3 = data.charCodeAt(i++);

            bits = o1 << 16 | o2 << 8 | o3;

            h1 = bits >> 18 & 0x3f;
            h2 = bits >> 12 & 0x3f;
            h3 = bits >> 6 & 0x3f;
            h4 = bits & 0x3f;

            tmp_arr[ac++] = b64.charAt(h1) + b64.charAt(h2) + b64.charAt(h3) + b64.charAt(h4);
        } while (i < data.length);

        enc = tmp_arr.join('');
            var r = data.length % 3;

        return (r ? enc.slice(0, r - 3) : enc) + '==='.slice(r || 3);
    }

    /**
     * Conversion of array data to utf-8.
     */
    function toUtf8(param) {
        if (typeof param === 'string') {
            param = utf8_encode(param);
        } else {
            for (var i in param) {
                param[i] = toUtf8(param[i]);
            }
        }

        return param;
    }

    /**
     * Implementation of utf8 encoding.
     */
    function utf8_encode(argString) {
        if (argString === null || typeof argString === "undefined") {
            return "";
        }

        var string = (argString + '');
        var utftext = "",
            start, end, stringl = 0;

        start = end = 0;
        stringl = string.length;
        for (var n = 0; n < stringl; n++) {
            var c1 = string.charCodeAt(n);
            var enc = null;

            if (c1 < 128) {
                end++;
            } else if (c1 > 127 && c1 < 2048) {
                enc = String.fromCharCode((c1 >> 6) | 192) + String.fromCharCode((c1 & 63) | 128);
            } else {
                enc = String.fromCharCode((c1 >> 12) | 224) + String.fromCharCode(((c1 >> 6) & 63) | 128) + String.fromCharCode((c1 & 63) | 128);
            }
            if (enc !== null) {
                if (end > start) {
                    utftext += string.slice(start, end);
                }
                utftext += enc;
                start = end = n + 1;
            }
        }

        if (end > start) {
            utftext += string.slice(start, stringl);
        }

        return utftext;
    }

}
