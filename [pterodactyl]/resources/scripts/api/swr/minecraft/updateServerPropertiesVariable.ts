import http from '@/api/http';

export interface ServerVariable {
    envVariable: string,
    serverValue: string,
}

export default async (uuid: string, key: string, value: string): Promise<[ ServerVariable, string ]> => {
    const { data } = await http.put(`/api/client/servers/${uuid}/minecraft/properties`, { key, value });
    return data;
};
