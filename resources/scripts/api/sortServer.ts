import http from '@/api/http';

export default (uuid: string, oldIndex: number | undefined, newIndex: number | undefined): Promise<any> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/client/sortServer`, {uuid, oldIndex, newIndex}).then((data) => {
            resolve(data.data || []);
        }).catch(reject);
    });
};
