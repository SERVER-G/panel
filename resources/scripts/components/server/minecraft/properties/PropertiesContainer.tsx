//! React
import React from 'react';

//! Store
import { ServerContext } from '@/state/server';

//! API
import { httpErrorToHuman } from '@/api/http';
import getServerProperties from '@/api/swr/minecraft/getServerProperties';

//! Components
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import { ServerError } from '@/components/elements/ScreenBlock';
import Spinner from '@/components/elements/Spinner';
import VariableBox from './VariableBox';

//! Vendors
import tw from 'twin.macro';

const ServerPropertiesContainer = () => {
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const { data, error, isValidating, mutate } = getServerProperties(uuid);

    return (
        !data ?
            (!error || (error && isValidating)) ?
                <Spinner centered size={Spinner.Size.LARGE}/>
                :
                <ServerError
                    title={'Oops!'}
                    message={httpErrorToHuman(error)}
                    onRetry={() => mutate()}
                />
            :
            <ServerContentBlock title={'Server Properties'} showFlashKey={'server:properties'}>
                <div css={tw`grid gap-8 md:grid-cols-2`}>
                    {data.variables.map(variable => <VariableBox key={variable.envVariable} variable={variable}/>)}
                </div>
            </ServerContentBlock>
    );
};

export default ServerPropertiesContainer;
