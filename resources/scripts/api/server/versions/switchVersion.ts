import http from '@/api/http';

export default (uuid: string, group: number, method: string, key: string, deleteFiles: number): Promise<any> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/client/servers/${uuid}/versions/switch`, {
            group, method, key, deleteFiles,
        }, {
            timeout: 120000,
        }).then((data) => {
            resolve(data.data || []);
        }).catch(reject);
    });
};
