<?php declare(strict_types = 1);

namespace Bug15234;

class SwitchApi
{
    /**
     * @return mixed
     */
    private function jsonDecode(string $json)
    {
        return json_decode($json, true, 512, \JSON_BIGINT_AS_STRING | \JSON_THROW_ON_ERROR);
    }

    /**
     * @param 'get' $method
     *
     * @return array<string, mixed>
     */
    public function sendRequest(string $method, string $path)
    {
        return [];
    }

    /**
     * @return array{eventTime: 0}|array{eventTime: int, indicatorType: string, signalLevel: string, signal: int}
     */
    public function queryDeviceSignalStrength(string $deviceId): array
    {
        return $this->sendRequest('get', '/v2.0/cloud/thing/' . $deviceId . '/WiFi/signal');
    }

    /**
     * @return array<string, array{custom_name: string, dp_id: int, time: int, type: string, value: scalar}>
     */
    public function queryDeviceProperties(string $deviceId): array
    {
        $response = $this->sendRequest('get', '/v2.0/cloud/thing/' . $deviceId . '/shadow/properties');

        return array_combine(
            array_map(static fn ($v) => $v['code'], $response['properties']),
            array_map(static fn ($v) => array_diff_key($v, ['code' => true]), $response['properties'])
        );
    }

    /**
     * @return array{category: string, functions: array{type: string, values: array<string, scalar|list<string>>}}
     */
    public function queryDeviceFunctions(string $deviceId): array
    {
        $response = $this->sendRequest('get', '/v1.0/iot-03/devices/' . $deviceId . '/functions');

        $response['functions'] = array_combine(
            array_map(static fn ($v) => $v['code'], $response['functions']),
            array_map(function ($v) {
                $v['values'] = $this->jsonDecode($v['values']);

                return array_diff_key($v, ['code' => true, 'desc' => true, 'name' => true]);
            }, $response['functions'])
        );

        return $response;
    }
}
