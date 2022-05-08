import React from 'react';
import tw from 'twin.macro';
import GreyRowBox from '@/components/elements/GreyRowBox';
import styled from 'styled-components/macro';
import AskModal from '@/components/server/playermanager/AskModal';

const Code = styled.code`${tw`font-mono py-1 px-2 bg-neutral-900 rounded text-sm inline-block`}`;
const Label = styled.label`${tw`uppercase text-xs mt-1 text-neutral-400 block px-1 select-none transition-colors duration-150`}`;

interface Props {
    players: Players;
    onUpdate: () => void;
}

export interface Players {
    list: any[];
    players: Details;
}

interface Details {
    max: number;
    online: number;
}

export default ({ players, onUpdate }: Props) => {
    return (
        <>
            <div css={tw`rounded shadow-md bg-neutral-700`}>
                <div css={tw`bg-neutral-900 rounded-t p-3 border-b border-black`}>
                    Players <small>- {players.players.online}/{players.players.max}</small>
                </div>
            </div>
            {players.list.length < 1 ?
                <p css={tw`text-center text-sm text-neutral-400 pt-4 pb-4`}>
                    There are no players on the server.
                </p>
                :
                <>
                    {players.list.map((item, key) => (
                        <GreyRowBox $hoverable={false} css={tw`flex-wrap md:flex-nowrap mt-2`} key={key}>
                            <div css={tw`flex items-center w-full md:w-auto`}>
                                <div css={tw`pl-4 pr-6 text-neutral-400`}>
                                    <img src={`//crafatar.com/avatars/${item.id}`} alt={'Player Skin'} width={48} />
                                </div>
                            </div>
                            <div css={tw`mt-4 w-full md:mt-0 md:w-40 mr-4`}>
                                <Code>{item.name}</Code>
                                <Label>Username</Label>
                            </div>
                            <div css={tw`mt-4 w-full md:mt-0 md:flex-1 md:w-auto mr-4`}>
                                <Code>{item.id}</Code>
                                <Label>UUID</Label>
                            </div>
                            <div css={tw`w-full md:flex-none md:w-4/12 md:text-center mt-4 md:mt-0 ml-4 flex items-center justify-end`}>
                                <AskModal
                                    buttonColor={item.isOp ? 'red' : 'green'}
                                    buttonText={item.isOp ? 'DEOP' : 'OP'}
                                    title={`${item.IsOp ? 'DEOP' : 'OP'} Player`}
                                    message={`Are you sure that you want to ${item.IsOp ? 'deop' : 'op'} <b>${item.name}</b>?`}
                                    command={`${item.isOp ? 'deop' : 'op'} ${item.name}`}
                                    onPerformed={() => onUpdate()}
                                />
                                <AskModal
                                    buttonColor={item.isWhitelist ? 'grey' : 'primary'}
                                    buttonText={item.isWhitelist ? 'Remove from Whitelist' : 'Add to Whitelist'}
                                    title={item.isWhitelist ? 'Remove Player from Whitelist' : 'Add Player to Whitelist'}
                                    message={`Are you sure that you want to ${item.isWhitelist ? 'remove' : 'add'} <b>${item.name}</b> ${item.isWhitelist ? 'from' : 'to'} the whitelist?`}
                                    command={`whitelist ${item.isWhitelist ? 'remove' : 'add'} ${item.name}`}
                                    onPerformed={() => onUpdate()}
                                />
                                <AskModal
                                    buttonColor={'red'}
                                    buttonSecondary
                                    buttonText={'Kick'}
                                    title={'Kick Player'}
                                    message={`Are you sure that you want to kick <b>${item.name}</b>?`}
                                    command={`kick ${item.name}`}
                                    onPerformed={() => onUpdate()}
                                />
                                <AskModal
                                    buttonColor={'red'}
                                    buttonText={'Ban'}
                                    title={'Ban Player'}
                                    message={`Are you sure that you want to ban <b>${item.name}</b>?`}
                                    command={`ban ${item.name}`}
                                    onPerformed={() => onUpdate()}
                                />
                                <AskModal
                                    buttonColor={'red'}
                                    buttonSecondary
                                    buttonText={'Ban IP'}
                                    title={'Ban IP'}
                                    message={`Are you sure that you want to ip ban <b>${item.name}</b>?`}
                                    command={`ban-ip ${item.name}`}
                                    onPerformed={() => onUpdate()}
                                />
                            </div>
                        </GreyRowBox>
                    ))}
                </>
            }
        </>
    );
}
;
