import useSWR from 'swr';
import http from '@/api/http';

export interface ServerVariable {
    envVariable: string,
    serverValue: string,
}

interface Response {
    variables: ServerVariable[];
}

export default (uuid: string) => useSWR([ uuid, '/minecraft/properties' ], async (): Promise<Response> => {
    const { data } = await http.get(`/api/client/servers/${uuid}/minecraft/properties`);

    return { variables: data.data };
}, { errorRetryCount: 3 });

// export default (uuid: string) => {
//     return useSWR<ServerVariable[]>([ 'server:properties', uuid ], async () => {
//         const { data } = await http.get(`/api/client/servers/${uuid}/minecraft/properties`);

//         return (data.data || []);
//     }, { revalidateOnFocus: false, revalidateOnMount: false });
// };
