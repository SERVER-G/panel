import React, { useEffect, useState } from 'react';
import { bytesToHuman, megabytesToHuman } from '@/helpers';
import { ServerContext } from '@/state/server';
import stripAnsi from 'strip-ansi';

// Plugins
import { useDeepMemoize } from '@/plugins/useDeepMemoize';
import useFlash from '@/plugins/useFlash';

// Events
import { SocketEvent, SocketRequest } from '@/components/server/events';

// API
import getServerDatabases from '@/api/server/databases/getServerDatabases';
import getServerBackups from '@/api/swr/getServerBackups';
import getServerSubusers from '@/api/server/users/getServerSubusers';
import { httpErrorToHuman } from '@/api/http';
import sendToHastebin from '@/api/server/sendToHastebin';

// Components
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import CopyOnClick from '@/components/elements/CopyOnClick';
import UptimeDuration from '@/components/server/UptimeDuration';
import Spinner from '@/components/elements/Spinner';
import Button, { LinkButton } from '@/components/elements/Button';

// Style
import tw, { TwStyle } from 'twin.macro';
import { faCircle, faEthernet, faHdd, faMemory, faMicrochip, faServer, faHeadset, faSitemap, faDatabase, faArchive, faUpload, faDownload, faUsers } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';

const largeTextsSize = false;

interface Stats {
    memory: number;
    cpu: number;
    disk: number;
    uptime: number;
    rx: number
    tx: number
}

interface ServersDetailsValueProp {
    name: string;
    value: string | number;
    icon: any;
    copy?: boolean;
    limit?: string | number;
}

function statusToColor (status: string | null, installing: boolean): TwStyle {
    if (installing) {
        status = '';
    }

    switch (status) {
        case 'offline':
            return tw`text-red-500`;
        case 'running':
            return tw`text-green-500`;
        default:
            return tw`text-yellow-500`;
    }
}

const ServerDetailsValue = ({ name, icon, value, copy, limit }: ServersDetailsValueProp) => {
    return (
        <>
            {copy ?
                <CopyOnClick text={value} >
                    <p css={largeTextsSize ? tw`text-sm mt-2 font-medium` : tw`text-xs mt-2 font-medium`}>
                        <FontAwesomeIcon icon={icon} fixedWidth css={tw`mr-1`} />
                        {name + ': '}
                        <span css={tw`text-neutral-300 font-normal ml-1`} >{value}</span>
                        {limit ? <span css={tw`text-neutral-500`}> / {limit}</span> : null}
                    </p>
                </CopyOnClick>
                :
                <p css={largeTextsSize ? tw`text-sm mt-2 font-medium` : tw`text-xs mt-2 font-medium`}>
                    <FontAwesomeIcon icon={icon} fixedWidth css={tw`mr-1`} />
                    {name + ': '}
                    <span css={tw`text-neutral-300 font-normal ml-1`} >{value}</span>
                    {limit ? <span css={tw`text-neutral-500`}> / {limit}</span> : null}
                </p>
            }
        </>
    );
};

const ServerDetailsBlock = () => {
    // Hooks
    const [ stats, setStats ] = useState<Stats>({ memory: 0, cpu: 0, disk: 0, uptime: 0, rx: 0, tx: 0 });
    const { addError, clearFlashes } = useFlash();
    const [ uploading, setUploading ] = useState<boolean>(false);
    const [ logsURL, setLogsURL ] = useState<string>();

    const status = ServerContext.useStoreState(state => state.status.value);
    const connected = ServerContext.useStoreState(state => state.socket.connected);
    const instance = ServerContext.useStoreState(state => state.socket.instance);
    const id = ServerContext.useStoreState(state => state.server.data!.id);
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);

    const name = ServerContext.useStoreState(state => state.server.data!.name);
    const primaryAllocation = ServerContext.useStoreState(state => state.server.data!.allocations.filter(alloc => alloc.isDefault).shift());
    const node = ServerContext.useStoreState(state => state.server.data!.node);
    const { data: backups, error, isValidating } = getServerBackups();

    // Status
    const isInstalling = ServerContext.useStoreState(state => state.server.data!.isInstalling);
    const isTransferring = ServerContext.useStoreState(state => state.server.data!.isTransferring);

    // Limits
    const limits = ServerContext.useStoreState(state => state.server.data!.limits);
    const diskLimit = limits.disk ? megabytesToHuman(limits.disk) : 'Unlimited';
    const memoryLimit = limits.memory ? megabytesToHuman(limits.memory) : 'Unlimited';
    const cpuLimit = limits.cpu !== 0 ? limits.cpu + '%' : 'Unlimited';
    const databaseLimit = ServerContext.useStoreState(state => state.server.data!.featureLimits.databases);
    const backupLimit = ServerContext.useStoreState(state => state.server.data!.featureLimits.backups);

    const databases = useDeepMemoize(ServerContext.useStoreState(state => state.databases.data));
    const setDatabases = ServerContext.useStoreActions(state => state.databases.setDatabases);
    const subusers = ServerContext.useStoreState(state => state.subusers.data);
    const setSubusers = ServerContext.useStoreActions(actions => actions.subusers.setSubusers);

    // Hastebin Post
    const [ log, setLog ] = useState<string[]>([]);
    const addLog = (data: string) =>
        setLog((prevLog) => [
            ...prevLog,
            data.startsWith('>') ? data.substring(1) : data,
        ]);

    const statsListener = (data: string) => {
        let stats: any = {};
        try {
            stats = JSON.parse(data);
        } catch (e) {
            return;
        }

        setStats({
            memory: stats.memory_bytes,
            cpu: stats.cpu_absolute,
            disk: stats.disk_bytes,
            uptime: stats.uptime || 0,
            rx: stats.network.rx_bytes,
            tx: stats.network.tx_bytes,
        });
    };

    useEffect(() => {
        if (!connected || !instance) {
            return;
        }

        instance.addListener(SocketEvent.STATS, statsListener);
        instance.addListener(SocketEvent.CONSOLE_OUTPUT, addLog);
        instance.send(SocketRequest.SEND_STATS);

        return () => {
            instance.removeListener(SocketEvent.STATS, statsListener);
            instance.removeListener(SocketEvent.CONSOLE_OUTPUT, addLog);
        };
    }, [ instance, connected ]);

    useEffect(() => {
        clearFlashes('databases');
        clearFlashes('users');

        getServerDatabases(uuid)
            .then(databases => setDatabases(databases))
            .catch(error => {
                console.error(error);
                addError({ key: 'databases', message: httpErrorToHuman(error) });
            });
        getServerSubusers(uuid)
            .then(subusers => setSubusers(subusers))
            .catch(error => {
                console.error(error);
                addError({ key: 'users', message: httpErrorToHuman(error) });
            });
    }, []);

    function uploadLogs () {
        let logs = `---------------------------------------------------------------------------------------
Uploaded on: ${new Date()}
Status: ${status}
Name: ${name}
Server ID: ${uuid}
Node: ${node}
CPU: ${stats.cpu.toFixed(2) + '%'}
RAM: ${bytesToHuman(stats.memory)}/${memoryLimit}
Disk: ${bytesToHuman(stats.disk)}/${diskLimit}
---------------------------------------------------------------------------------------\n\n`;
        logs += stripAnsi(log.map((it) => it.replace('\r', '')).join('\n'));
        setUploading(true);
        sendToHastebin(uuid, logs).then((result) => {
            setUploading(false);
            setLogsURL(`https://www.toptal.com/developers/hastebin/${result.key}`);
        });
    }

    if (!backups || (error && isValidating)) {
        return (
            <TitledGreyBox css={tw`break-words`} title={name} icon={faServer}>
                <Spinner size={'large'} centered />
            </TitledGreyBox>
        );
    }

    return (
        <TitledGreyBox css={tw`break-words`} title={name} icon={faServer}>
            <p css={largeTextsSize ? tw`text-sm uppercase` : tw`text-xs uppercase`}>
                <FontAwesomeIcon
                    icon={faCircle}
                    fixedWidth
                    css={[
                        tw`mr-1`,
                        statusToColor(status, isInstalling || isTransferring),
                    ]}
                />
                &nbsp;{!status ? 'Connecting...' : (isInstalling ? 'Installing' : (isTransferring) ? 'Transferring' : status)}
                {stats.uptime > 0 &&
                    <span css={tw`ml-2 lowercase`}>
                        (<UptimeDuration uptime={stats.uptime / 1000} />)
                    </span>
                }
            </p>

            {primaryAllocation!.alias ?
                <>
                    <ServerDetailsValue name={'Alias'} icon={faEthernet} copy value={`${primaryAllocation!.alias}:${primaryAllocation!.port}`} />
                    <ServerDetailsValue name={'IP'} icon={faEthernet} copy value={`${primaryAllocation!.ip}:${primaryAllocation!.port}`} />
                </>
                :
                <ServerDetailsValue name={'IP'} icon={faEthernet} copy value={`${primaryAllocation!.ip}:${primaryAllocation!.port}`} />
            }
            <ServerDetailsValue name={'Server ID'} icon={faHeadset} copy value={id} />
            <ServerDetailsValue name={'Node'} icon={faSitemap} value={node} />
            <ServerDetailsValue name={'CPU'} icon={faMicrochip} value={stats.cpu.toFixed(2) + '%'} limit={cpuLimit} />
            <ServerDetailsValue name={'Memory'} icon={faMemory} value={bytesToHuman(stats.memory)} limit={memoryLimit} />
            <ServerDetailsValue name={'Disk'} icon={faHdd} value={bytesToHuman(stats.disk)} limit={diskLimit} />
            {status !== 'offline' && (
                <>
                    <ServerDetailsValue name={'Upload'} icon={faUpload} value={bytesToHuman(stats.tx)} />
                    <ServerDetailsValue name={'Download'} icon={faDownload} value={bytesToHuman(stats.rx)} />
                </>
            )}
            {subusers.length !== 0 ? <ServerDetailsValue name={'Users'} icon={faUsers} value={subusers.length} /> : null}
            {databaseLimit !== 0 ? <ServerDetailsValue name={'Databases'} icon={faDatabase} value={databases.length} limit={databaseLimit} /> : null}
            {backupLimit !== 0 ? <ServerDetailsValue name={'Backups'} icon={faArchive} value={backups!.pagination.total} limit={backupLimit} /> : null}
            <div css={tw`mt-2 flex text-xs justify-center`}>
                <Button
                    size={'xsmall'}
                    css={tw`mr-2`}
                    disabled={uploading}
                    onClick={() => {
                        uploadLogs();
                    }}
                >
                    Send Logs
                </Button>
                {logsURL && (
                    <>
                        <LinkButton
                            size='xsmall'
                            color='green'
                            css={tw`mr-2`}
                            href={logsURL}
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            Open Logs
                        </LinkButton>
                        <CopyOnClick text={logsURL} >
                            <Button
                                size={'xsmall'}
                                color='grey'
                            >
                                Copy Logs
                            </Button>
                        </CopyOnClick>
                    </>
                )}

            </div>
        </TitledGreyBox>
    );
};

export default ServerDetailsBlock;
