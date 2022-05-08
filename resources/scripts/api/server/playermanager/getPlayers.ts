import http from '@/api/http';
import { PlayerManagerResponse } from '@/components/server/playermanager/PlayerManagerContainer';

export default async (uuid: string): Promise<PlayerManagerResponse> => {
    const { data } = await http.get(`/api/client/servers/${uuid}/playermanager`, {});

    return (data.data || []);
};
