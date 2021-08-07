<?php

namespace Leochenftw\Restful;
use SilverStripe\Core\ClassInfo;
use Leochenftw\Restful\IRestSerializer;

class SerializerFactory {
    private static $lookup = [
        'json' => 'application/json',
        'html' => 'text/html',
        'xml' => 'application/xml',
        'yaml' => 'application/yaml'
    ];

    public static function create($mimeType='application/json')
    {
        $availableSerializers = ClassInfo::implementorsOf(IRestSerializer::class);
        foreach($availableSerializers as $serializer) {
            /** @var IRestSerializer $instance */
            $instance = new $serializer();
            if($instance->active() && $instance->contentType() === $mimeType) {
                return $instance;
            }
        }
        throw new RestUserException("Requested Accept '$mimeType' not supported", 404);
    }

    public static function create_from_request($request)
    {
        if($type = $request->getVar('accept')) {
            try {
                if(array_key_exists($type, self::$lookup)) {
                    return self::create(self::$lookup[$type]);
                }
            } catch(\Exception $e) {}
        }
        $types = $request->getAcceptMimetypes();
        foreach($types as $type) {
            try {
                return self::create($type);
            } catch(RestUserException $e) {}
        }
        return self::create();
    }
}
