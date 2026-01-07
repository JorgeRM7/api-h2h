<?php

namespace App\Utils;

use Eher\OAuth\SignatureMethod;

class HmacSha256 extends SignatureMethod
{
    public function get_name()
    {
        return 'HMAC-SHA256';
    }

    public function build_signature($request, $consumer, $token)
    {
        $baseString = $request->get_signature_base_string();
        $keyParts  = [];

        $keyParts[] = rawurlencode($consumer->secret);
        $keyParts[] = $token ? rawurlencode($token->secret) : '';

        $key = implode('&', $keyParts);

        return base64_encode(hash_hmac('sha256', $baseString, $key, true));
    }
}
